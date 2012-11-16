<?php
/**
 * Test suite for the CodeIgniter Redis library
 *
 * @see ../libraries/Redis.php
 */
define('BASEPATH', TRUE);
require_once('libraries/Redis.php');
require_once('Stubs.php');


class RedisTest extends PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		$this->redis = new Redis();
	}
	
	/**
	 * Test encode request
	 *
	 * Performs low-level tests on the encoding from
	 * a command to the Redis protocol.
	 */
	public function test_encode_request()
	{
		$method = new ReflectionMethod('Redis', '_encode_request');
		$method->setAccessible(TRUE);
		
		// Individual command
		$this->assertEquals(
			$method->invoke(new Redis, 'PING'), 
			"*1\r\n$4\r\nPING\r\n"
		);
		
		// Command with a key and value, passed as a single argument
		$this->assertEquals(
			$method->invoke(new Redis, 'SET key value'), 
			"*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n"
		);
		
		// Command with a key and value, passed as an array
		$this->assertEquals(
			$method->invoke(new Redis, 'SET', array('key' => 'value')), 
			"*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n"
		);
		
		// Command with a multiple keys and values, passed as a string
		$this->assertEquals(
			$method->invoke(new Redis, 'HMSET key key1 value1 key2 value2'), 
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n"
		);
		
		// Command with a multiple keys and values, passed as an array
		$this->assertEquals(
			$method->invoke(new Redis, 'HMSET key', array('key1' => 'value1', 'key2' => 'value2')), 
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n"
		);
		
		// Command with a multiple keys and values, passed as an array, with spaces
		$this->assertEquals(
			$method->invoke(new Redis, 'HMSET key', array('key1' => 'value 1', 'key2' => 'value 2')), 
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$7\r\nvalue 1\r\n$4\r\nkey2\r\n$7\r\nvalue 2\r\n"
		);
		
	}
	
	/**
	 * Test overloading
	 *
	 * Tests the overloading of commands through the 
	 * __call magic method. The internals of the library
	 * are treated as a blackbox.
	 */
	public function test_overloading()
	{
		// Single command
		$this->assertEquals($this->redis->ping(), 'PONG');
		
		// Arguments as a string
		$this->assertEquals($this->redis->set('key value'), 'OK');
		$this->assertEquals($this->redis->get('key'), 'value');
		$this->redis->del('key');

		// Arguments as a seperate arguments
		$this->assertEquals($this->redis->set('key', 'value'), 'OK');
		$this->assertEquals($this->redis->get('key'), 'value');
		$this->redis->del('key');
		
		// Multiple arguments as a string
		$this->assertEquals($this->redis->hmset('key key1 value1 key2 value2'), 'OK');
		$this->assertEquals($this->redis->hget('key key1'), 'value1');
		$this->redis->del('key');

		// Multiple arguments as an array
		$this->assertEquals($this->redis->hmset('key', array('key1' => 'value1', 'key2' => 'value2')), 'OK');
		$this->assertEquals($this->redis->hget('key', 'key1'), 'value1');
		$this->redis->del('key');

	}
	
	/**
	 * Test info
	 */
	public function test_info()
	{
		$info = $this->redis->info();
		$this->assertTrue(isset($info['redis_version']));
		$this->assertTrue(isset($info['process_id']));
	}

	/**
	 * Test successively larger reads from a single line reply (after writing successively larger values)
	 * This is the only place in the code where the expected length of a response is not known in advance.
	 */
	public function test_chunk_reads()
	{

		/**
		 * Adapted from Chad Birch's answer found here: http://stackoverflow.com/a/853898
		 * Chad is awesome, you can check out his profile here: http://stackoverflow.com/users/41665/chad-birch
		 */
		$get_random_string = function($length)
		{
			$valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$random_string = "";
			$num_valid_chars = strlen($valid_chars);
			for ($i = 0; $i < $length; $i++)
			{
				$random_pick = mt_rand(1, $num_valid_chars);
				$random_char = $valid_chars[$random_pick-1];
				$random_string .= $random_char;
			}
			return $random_string;
		};

		$len = 512;
		while ($len < (30 * 1024))
		{
			$payload = $get_random_string($len);
			$wlen = strlen($payload);
			$this->redis->set('test', $payload);
			$rlen = strlen($this->redis->get('test'));
			$this->assertEquals($wlen, $rlen);
 			$len += 512;
		}
		$this->redis->del('test');
		return TRUE;
	}
}
