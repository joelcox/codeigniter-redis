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

	public static $config = array(
		'redis_default' => array(
			'host' => 'localhost',
			'port' => 6379,
			'password' => ''
		)
	);

	public function item($key)
	{
		if ( ! isset(self::$config[$key])) return NULL;
		return self::$config[$key];
	}

}

class Loader_stub {
	public function config($file)
	{
		return TRUE;
	}

}