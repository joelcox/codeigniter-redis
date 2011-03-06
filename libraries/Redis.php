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
	 * @return mixed
	 */
	private function _write_request($request)
	{
		
		fwrite($this->_connection, $request);
		return $this->_read_request($return);
		
	}
	
	/**
	 * Route each response to the appropriate interpreter
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
	 * Reads to amount of bits to be read and returns value within the pointer and that delimiter
	 * @return string
	 */
	private function _bulk_reply()
	{
		
		// Get the amount of bits to be read
		$value_length = (int) fgets($this->_connection);
		return fgets($this->_connection, $value_length + 1);
		
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