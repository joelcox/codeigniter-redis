<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CodeIgniter Redis
 *
 * A CodeIgniter library to interact with Redis
 *
 * @package        	CodeIgniter
 * @category    	Libraries
 * @author        	Joël Cox
 * @link 			https://github.com/joelcox/codeigniter-redis
 * @license         http://www.opensource.org/licenses/mit-license.html
 * 
 * Copyright (c) 2011 Joël Cox and contributers
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Redis
{
	
	private $_ci;				// CodeIgniter instance
	private $_password;			// Password for the server
	private $_connection;		// Connection handle
	
	public $host;				// Server host
	public $port;				// Server post where Redis is listening on
	public $debug;
	
	function __construct()
	{
		
		log_message('debug', 'Redis Class Initialized.');
		
		// Get a CI instance
		$this->_ci =& get_instance();
		
		// Load config
		$this->_ci->load->config('redis');
		$this->host = $this->_ci->config->item('redis_host');
		$this->port = $this->_ci->config->item('redis_port');
		$this->_password = $this->_ci->config->item('redis_password');
		
		// Connect to Redis
		$this->_connection = @fsockopen($this->host, $this->port, $errno, $errstr, 3);
		
		// Display an error message if connection failed
		if ( ! $this->_connection)
		{
			show_error('Could not connect to Redis at ' . $this->host . ':' . $this->port);
			
		}
	
		// Authenticate when needed
		$this->_auth();
		
	}
	
	/**
	 * String commands
	 */
	
	/**
	 * Sets key $key with value $value
	 * @param string name of the key
	 * @param string contents for the key
	 * @return boolean
	 */
	public function set($key, $value)
	{
		
		$request = $this->_encode_request('SET ' . $key . ' ' . $value);
		return $this->_write_request($request);
		
	}
	
	/**
	 * Gets key $key
	 * @param string name of the key
	 * @return string value of the key
	 */
	public function get($key)
	{
		$request = $this->_encode_request('GET ' . $key);
		return $this->_write_request($request);
		
	}
	
	/**
	 * Delete key(s)
	 * @param mixed keys to be deleted (array or string)
	 * @return boolean
	 */
	public function del($keys)
	{
		// Make sure we're dealing with a string that seperates keys by a space
		$keys = $this->_input_to_string($keys);
		
		$request = $this->_encode_request('DEL ' . $keys);
		return $this->_write_request($request);
		
	}
	
	/**
	 * Finds all keys that match $pattern
	 * @param string pattern to be matched
	 * @return array
	 */
	public function keys($pattern)
	{
		$request = $this->_encode_request('KEYS ' . $pattern);
		return $this->_write_request($request);
		
	}
	
	/**
	 * Connection commands
	 */
	
	/**
	 * Runs the AUTH command when password is set
	 * @return void
	 */
	private function _auth()
	{
		
		// Authenticate when password is set
		if ( ! empty($this->_password))
		{
				
			// Sent auth command to the server
			$request = $this->_encode_request('AUTH ' . $this->_password);
			 
			// See if we authenticated successfully
			if ( ! $this->_write_request($request))
			{
				show_error('Could not connect to Redis, invalid password');

			}
			
		}
		
	}
	
	/**
	 * Write the formatted request to the socket
	 * @param string request to be written
	 * @return mixed
	 */
	private function _write_request($request)
	{
		
		fwrite($this->_connection, $request);
		return $this->_read_request();
		
	}
	
	/**
	 * Route each response to the appropriate interpreter
	 * @return mixed
	 */
	private function _read_request()
	{
		
		$type = fgetc($this->_connection);
		
		switch ($type)
		{
			case '+':
				return $this->_single_line_reply();
				break;
			case '-':
				return $this->_error_reply();
				break;
			case ':':
				return $this->_integer_reply();
				break;
			case '$':
				return $this->_bulk_reply();
				break;
			case '*':
				return $this->_multi_bulk_reply();
				break;
			default:
				return false;
		}
		
	}
	
	/**
	 * Reads the reply before the EOF
	 * @return mixed
	 */	
	private function _single_line_reply()
	{
		$value = trim(fgets($this->_connection));
		
		if ($value == 'OK')
		{
			return TRUE;
		}
		else
		{
			return $value;
		}
		
	}
	
	/**
	 * Write error to log and return false
	 * @return boolean
	 */
	private function _error_reply()
	{
		// Extract the error message
		$error = substr(fgets($this->_connection), 4);
		log_message('error', 'Redis server returned an error: ' . $error);
		
		return FALSE;

	}
	
	/**
	 * Returns an integer reply
	 * @return integer
	 */
	private function _integer_reply()
	{
		return (int) fgets($this->_connection);
		
	}
	
	/**
	 * Reads to amount of bits to be read and returns value within the pointer and that delimiter
	 * @return string
	 */
	private function _bulk_reply()
	{
		
		// Get the amount of bits to be read
		$value_length = (int) fgets($this->_connection);	
		return @fgets($this->_connection, $value_length + 1);
		
	}
	
	private function _multi_bulk_reply()
	{
		
		// Get the amount of values in the response
		$total_values = (int) fgets($this->_connection);
		
		// Return null when there are no elements	
		if ($total_values == 0)
		{
			return NULL;
		}
				
		// Loop all values and add them to the response array
		for ($i = 0; $i < $total_values; $i++)
		{
			// Move the pointer to correct for the \n\r
			fgets($this->_connection, 2);
			$response[] = $this->_bulk_reply();
			fgets($this->_connection);		
			
		}
		
		return $response;
		
	}
	
	
	/**
	 * Encode plain-text request to Redis protocol format
	 * @see http://redis.io/topics/protocol
	 * @param string request in plain-text
	 * @return request encoded according to Redis protocol
	 */
	private function _encode_request($request)
	{
		$slices = explode(' ', $request);
		$arguments = count($slices);
		
		$request = "*" . $arguments . "\r\n";
		
		foreach ($slices as $slice)
		{
			$request .= "$" . strlen($slice) . "\r\n" . $slice ."\r\n";
		}
		
		return $request;
		
	}
	
	/**
	 * Converts a input to string with spaces seperating values
	 * @param mixed 
	 * @return array
	 */
	private function _input_to_string($input)
	{
	
		if (is_array($input))
		{
			foreach ($input as $element)
			{
				@$string .= $element . ' ';
			}
				
		}
		else
		{
			$string = $input;
			
		}
		
		return $string;
		
	}
	
	/**
	 * Execute in debug mode
	 * @return void
	 */
	public function debug()
	{
		$this->debug = TRUE;
	}
	
	function __destruct()
	{
		fclose($this->_connection);
	}
	
}