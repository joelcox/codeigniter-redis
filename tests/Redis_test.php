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

	public function __construct()
	{
		$this->redis = new CI_Redis();

		$this->reflection = new ReflectionMethod('CI_Redis', '_encode_request');
		$this->reflection->setAccessible(TRUE);
	}

	public function setUp()
	{
		$this->redis->flushdb();
	}

	/**
	 * Test encode request
	 *
	 * Performs low-level tests on the encoding from
	 * a command to the Redis protocol.
	 */
	public function test_encode_single_command()
	{
		$this->assertEquals(
			$this->reflection->invoke($this->redis, 'PING'),
			"*1\r\n$4\r\nPING\r\n"
		);
	}

	public function test_encode_multiple_args()
	{
		$this->assertEquals(
			$this->reflection->invoke($this->redis, 'SET', array('key', 'value')),
			"*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n"
		);
	}

	public function test_encode_array()
	{
		// Command with a multiple keys and values, passed as an array
		$this->assertEquals(
			$this->reflection->invoke($this->redis, 'HMSET', array('key', array('key1' => 'value1', 'key2' => 'value2'))),
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n"
		);
	}

	public function test_encode_array_with_spaces()
	{
		// Command with a multiple keys and values, passed as an array, with spaces
		$this->assertEquals(
			$this->reflection->invoke($this->redis, 'HMSET', array('key', array('key1' => 'value 1', 'key2' => 'value 2'))),
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$7\r\nvalue 1\r\n$4\r\nkey2\r\n$7\r\nvalue 2\r\n"
		);
	}

	public function test_encode_array_mixed_values()
	{
		// Command with a multiple keys and values, passed as an array, with spaces
		$this->assertEquals(
			$this->reflection->invoke($this->redis, 'HMSET', array('key', array('key1' => 'value 1', 'value 2'))),
			"*5\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$7\r\nvalue 1\r\n$7\r\nvalue 2\r\n"
		);
	}

	/**
	 * Test overloading
	 *
	 * Tests the overloading of commands through the
	 * __call magic method. The internals of the library
	 * are treated as a blackbox.
	 */
	public function test_overloading_single_command()
	{
		$this->assertEquals($this->redis->ping(), 'PONG');
	}

	public function test_overloading_multiple_args()
	{
		$this->assertEquals($this->redis->set('key', 'value'), 'OK');
		$this->assertEquals($this->redis->get('key'), 'value');
	}

	public function test_overloading_array()
	{
		$this->assertEquals($this->redis->hmset('key', array('key1' => 'value1', 'key2' => 'value2')), 'OK');
		$this->assertEquals($this->redis->hget('key', 'key1'), 'value1');
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
		public function test_info_section()
	{
		$info = $this->redis->info("memory");
		$this->assertTrue(isset($info['used_memory_human']));
		$this->assertFalse(isset($info['process_id']));
	}

	/**
	 * Plain text command
	 *
	 * Test plain text commands using the command method.
	 */
	public function test_plain_text_command()
	{
		$this->assertEquals($this->redis->command('SET foo bar'), 'OK');
		$this->assertEquals($this->redis->command('GET foo'), 'bar');
	}

	/**
	 * Commands
	 *
	 * Test individual Redis commands so we have a more granular way
	 * of testing the different notations and commands
	 */
	public function test_command_set()
	{
		$this->assertEquals($this->redis->set('foo', 'bar'), 'OK');
	}

	public function test_command_del_str()
	{
		$this->redis->set('foo', 'bar');
		$this->redis->del('foo', 1);
	}

	public function test_command_del_multiple_args()
	{
		$this->redis->set('foo', 'bar');
		$this->redis->set('spam', 'eggs');
		$this->assertEquals($this->redis->del('foo', 'spam'), 2);
	}

	public function test_command_del_array()
	{
		$this->redis->set('foo', 'bar');
		$this->redis->set('spam', 'eggs');
		$this->assertEquals($this->redis->del(array('foo', 'spam')), 2);
	}

	public function test_command_lpush_multiple_args()
	{
		$this->assertEquals($this->redis->lpush('foo', 'spam', 'bacon', 'eggs'), 3);
	}

	public function test_command_lpush_array()
	{
		$this->assertEquals($this->redis->lpush('foo', array('spam', 'bacon', 'eggs')), 3);
	}

	public function test_command_lrange_multiple_args()
	{
		$this->redis->lpush('foo', 'spam', 'bacon', 'eggs');
		$this->assertEquals($this->redis->lrange('foo', 1, 2), array('bacon', 'spam'));
	}

	/**
	 * Empty hash fields
	 * @see Issue #33
	 */
	public function test_empty_hash_values()
	{
		$this->redis->hmset('hash', array('foo' => 'bar', 'bacon' => ''));
		$this->assertEquals($this->redis->hvals('hash'), array('bar', ''));
		$this->assertEquals($this->redis->set('foo', 'bar'), 'OK');
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
			$random_string = '';
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

	/**
	 * Test connection groups
	 *
	 * Supporting multiple connection groups impacted the structure of the
	 * configuration file. All valid options are tested here.
	 */
	public function test_connection_groups()
	{
		// Original configuration file
		Config_stub::$config = array(
			'redis_host' => 'localhost',
			'redis_port' => 6379,
			'redis_password' => ''
		);

		$redis = new CI_Redis();
		$this->assertEquals($redis->ping(), 'PONG');

		// Multiple connection groups
		Config_stub::$config = array(
			'redis_default' => array(
				'host' => 'localhost',
				'port' => 6379,
				'password' => ''
			),
			'redis_slave' => array(
				'host' => 'localhost',
				'port' => 6379,
				'password' => ''
			)
		);

		$redis_default = new CI_Redis();
		$this->assertEquals($redis_default->ping(), 'PONG');

		$redis_slave = new CI_Redis(array('connection_group' => 'slave'));
		$this->assertEquals($redis_slave->ping(), 'PONG');

	}
}
