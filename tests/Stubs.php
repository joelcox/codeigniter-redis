<?php
/**
 * Stubs for the CodeIgniter Redis library
 *
 * @see ../libraries/Redis.php
 */

function get_instance()
{
	$ci = new StdClass();
	$ci->config = new Config_stub();
	$ci->load = new Loader_stub();

	return $ci;
}

function log_message($level, $message)
{
	return TRUE;
}

function show_error($message)
{
	return TRUE;
}

class Config_stub {

	public function __construct()
	{
		$this->config = array('redis_host' => 'localhost', 'redis_port' => 6379, 'redis_password' => '');
	}

	public function item($key)
	{
		return $this->config[$key];
	}

}

class Loader_stub {
	public function config($file)
	{
		return TRUE;
	}

}