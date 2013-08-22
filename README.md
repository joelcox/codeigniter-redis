CodeIgniter Redis
=================

A CodeIgniter interface for the Redis data store. This library tries to adhere to the [defined protocol](http://redis.io/topics/protocol), including responses.

[![Build Status](https://secure.travis-ci.org/joelcox/codeigniter-redis.png?branch=develop)](http://travis-ci.org/joelcox/codeigniter-redis)

Requirements
------------
1. PHP 5+
2. [CodeIgniter 2.0+](http://codeigniter.com)
3. A [Redis server](http://redis.io) compatible with the unified request protocol (Redis 1.2+)

Spark
-----
This library is also released as a [Spark](http://getsparks.org). If you use this library in any other way, **don't copy the autoload.php to your config directory**.

Breaking changes
----------------

As of v0.4, this library does not longer support the plain text syntax for overloaded commands (i.e. `$this->redis->set('foo bar')`). Please pass extra command arguments as real PHP arguments instead (i.e. `$this->redis->set('foo', 'bar')`). You can still use the plain text syntax using the `command` method (e.g. `$this->redis->command('SET foo bar')`) if you need this functionality.

Documentation
-------------

### Configuration
This library expects a configuration file to function correctly. A template for this file is provided with the library. 

### Multiple connection groups 
If you want to use multiple Redis servers, you can add an additional array to the configuration file with the details of this server. 

```
$config['redis_slave']['host'] = 'otherhost';
$config['redis_slave']['port'] = '6379';
$config['redis_slave']['password'] = '';
```

To use this connection group, you must create a new instance of this library like this:

```
$this->load->library('redis', array('connection_group' => 'slave'), 'redis_slave');
$this->redis_slave->command('PING')
```

This will create a new object named `redis_slave` which will use the configuration options of the `slave` connection group. The default connection group is loaded by when no connection group is specified.

### Generic command
You can execute any command using the `command()` method, just like you're using [redis-cli](http://code.google.com/p/redis/wiki/RedisCLI).

    $this->redis->command('PING');

This library also support PHP's [overloading](http://php.net/manual/en/language.oop5.overloading.php) functionality. This means you can call undefined methods, which are then dynamically created for you. These calls are routed to the generic `__call()` method. You can also pass in arrays.

    $this->redis->hmset('foohash', array('key1' => 'value1', 'key2' => 'value2'));

### Examples

Set a new key with a value and retrieve it again

    $this->redis->set('foo', 'bar');

Get a value by its key

    $this->redis->get('foo');
    
Delete a bunch of keys

	$this->redis->del(array('foo', 'foo2'));
	
### Working with lists

Because PHP lacks basic list and dictionary/map/hash data types, this library tries to detect this on its own. This is done by using the following heuristic; if the smallest key in an array equals 0 and the largest key equals the length of the array - 1, the array is considered to be a list. In this case, the library's internals will automatically strip away the keys before passing the array to the Redis server.
	
Contributing
------------
I am a firm believer of social coding, so if you find a bug, please fork this code on [GitHub](http://github.com/joelcox/codeigniter-redis) and squash it. I will be happy to merge it back in to the code base (and add you to the "Thanks to" section). If you're not too comfortable using Git or messing with the inner workings of this library, please open [a new issue](http://github.com/joelcox/codeigniter-redis/issues). 

License
-------
This library is released under the MIT license.

Thanks to
---------
* [Eric Greer](https://github.com/integrii)
* [Martijn Pannevis](http://martijnpannevis.nl/blog/)
* [Muhammad Fikri](https://github.com/swznd) for fixing the 8kb choking bug.
* [Alex Williams](http://www.alexwilliams.ca) for his working on connection groups. ✨
* [Daniel Hunsaker](http://danhunsaker.wordpress.com) for fixing a bug related to passing 3+ arguments and his input on different issues.
* [Tim Post](http://alertfalse.com/) for taking the time to fix a long standing 'space' bug.
* [Lucas Nolte](http://91media.de/) for filing bug reports and submitting patches.

Cheers,
–– [Joël Cox](http://joelcox.nl)
