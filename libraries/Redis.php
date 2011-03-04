<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Redis
{
	
	public $host = 'localhost';
	public $port = '6379';
	public $debug;
	
	private $_connection;
	
	function __construct()
	{
		
		log_message('debug', 'Redis Class Initialized.');
		
		$this->_connection = fsockopen($this->host, $this->port, $errno, $errstr, 3);
		
	}
	
	public function set($key, $value)
	{
		
		$request = $this->_encode_request('SET ' . $key . ' ' . $value);
		
		return $this->_write_request($request);
		
	}
	
	public function get($key)
	{
		$request = $this->_encode_request('GET ' . $key);
		return $this->_write_request($request, TRUE);
		
	}
	
	/**
	 * Write the formatted request to the socket
	 * @param string request to be written
	 * @param boolean whether to return the server response or just a boolean
	 * @return mixed
	 */
	private function _write_request($request, $return = FALSE)
	{
		
		fwrite($this->_connection, $request);
		return $this->_read_request($return);
		
	}
	
	private function _read_request($return = FALSE)
	{
		
		$response = trim(fgets($this->_connection));
				
		// Return the response directly when when doing a read or in debug mode
		if ($return === TRUE OR $this->debug)
		{		
			return $response;
			
		}
		else
		{
			// Check if we get a success response
			if ($response == '+OK')
			{
				return TRUE;
				
			}
			else
			{
				return FALSE;
				
			}
			
		}
		
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