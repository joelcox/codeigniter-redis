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
	
	public function test_potocol()
	{
		$this->assertEquals($this->redis->command('PING'), 'PONG');
	}
	
	public function test_overloading()
	{
		$this->assertEquals($this->redis->hmset('myhash field1 Hello field2 World'), 'OK');
		$this->assertEquals($this->redis->hget('myhash field1'), 'Hello');
		$this->assertEquals($this->redis->del('myhash'), 1);
	}
	
	public function test_misc()
	{
		$this->assertEquals($this->redis->set('key', 'value'), 'OK');
		$this->assertEquals($this->redis->set('key value'), 'OK');
		$this->assertEquals($this->redis->get('key'), 'value');
	}	
	
}