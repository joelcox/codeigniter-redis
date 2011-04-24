<?php 

/**
 * Small test suite for the Redis client library
 *
 * @see /application/libraries/Redis.php
 */
if (! defined('BASEPATH')) exit('No direct script access');

class Test_redis extends CI_Controller {
	
	function index() {
		
		$this->load->spark('redis/dev');
		$this->load->library('unit_test');
		
		// Make sure we're running in strict test mode
		$this->unit->use_strict(TRUE);
		
		// Generic command
		$this->unit->run($this->redis->command('PING'), 'PONG', 'Generic command (PING!)');
		
		// Overloading
		$this->unit->run($this->redis->hmset('myhash field1 "Hello" field2 "World"'), 'OK', 'Overloading (__call())');
		$this->unit->run($this->redis->hget('myhash field1'), '"Hello"', 'Overloading (__call())');
		$this->unit->run($this->redis->del('myhash'), 1 , 'Overloading (__call())');
		
		// SET
		$this->unit->run($this->redis->set('key', 'value'), 'OK', 'Set a key with a value');
		
		// GET
		$this->unit->run($this->redis->get('key'), 'value', 'Get the value of a set key');
		
		// DEL
		$this->unit->run($this->redis->del('key'),  1, 'Delete a set key');
		$this->unit->run($this->redis->del('key'),  0, 'Delete a unset key');
		$this->redis->set('key', 'value');
		$this->unit->run($this->redis->del('key key2'),  1, 'Delete a keys (space)');
		$this->redis->set('key', 'value');
		$this->unit->run($this->redis->del('key, key2'),  1, 'Delete a keys (comma)');
		$this->redis->set('key', 'value');
		$this->unit->run($this->redis->del(array('key', 'key2')),  1, 'Delete keys (array)');
		$this->redis->set('key', 'value');
		$this->unit->run($this->redis->del('key, key2'),  1, 'Delete a set and unset key');
		
		// KEYS
		$this->redis->set('key', 'value');
		$this->redis->set('key2', 'value');
		$this->unit->run($this->redis->keys('key*'),  array('key2', 'key'), 'Get all keys matching "key"');
		$this->redis->del('key key2');

		// Display all results
		echo $this->unit->report();
		
	}

}