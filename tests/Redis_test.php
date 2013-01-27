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
		$this->redis = new Redis();
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
	public function test_encode_request()
	{
		$method = new ReflectionMethod('Redis', '_encode_request');
		$method->setAccessible(TRUE);

		// Individual command
		$this->assertEquals(
			$method->invoke($this->redis, 'PING'),
			"*1\r\n$4\r\nPING\r\n"
		);

		// Command with a key and value, passed as a single argument
		$this->assertEquals(
			$method->invoke($this->redis, 'SET key value'),
			"*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n"
		);

		// Command with a key and value, passed as an array
		$this->assertEquals(
			$method->invoke($this->redis, 'SET', array('key' => 'value')),
			"*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n"
		);

		// Command with a multiple keys and values, passed as a string
		$this->assertEquals(
			$method->invoke($this->redis, 'HMSET key key1 value1 key2 value2'),
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n"
		);

		// Command with a multiple keys and values, passed as an array
		$this->assertEquals(
			$method->invoke($this->redis, 'HMSET key', array('key1' => 'value1', 'key2' => 'value2')),
			"*6\r\n$5\r\nHMSET\r\n$3\r\nkey\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n"
		);

		// Command with a multiple keys and values, passed as an array, with spaces
		$this->assertEquals(
			$method->invoke($this->redis, 'HMSET key', array('key1' => 'value 1', 'key2' => 'value 2')),
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
	public function test_overloading_single_command()
	{
		// Single command
		$this->assertEquals($this->redis->ping(), 'PONG');
	}

	public function test_overloading_single_str()
	{
		$this->assertEquals($this->redis->set('key value'), 'OK');
		$this->assertEquals($this->redis->get('key'), 'value');
	}

	public function test_overloading_multiple_args()
	{
		$this->assertEquals($this->redis->set('key', 'value'), 'OK');
		$this->assertEquals($this->redis->get('key'), 'value');
	}

	public function test_overloading_single_str_as_assoc_array()
	{
		$this->assertEquals($this->redis->hmset('key key1 value1 key2 value2'), 'OK');
		$this->assertEquals($this->redis->hget('key key1'), 'value1');
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
	 * Static helper functions
	 */
	public function test_is_associative_array()
	{
		$this->assertFalse(Redis::is_associative_array(array('foo', 'bar')));

		$this->assertTrue(Redis::is_associative_array(array(
			1 => 'foo',
			2 => 'bar',
		)));

		$this->assertTrue(Redis::is_associative_array(array(
			0 => 'foo',
			2 => 'bar',
		)));

		$this->assertTrue(Redis::is_associative_array(array(
			'foo' => 'bar',
			'spam' => 'eggs',
		)));
	}
}