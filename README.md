CodeIgniter Redis
=================
A CodeIgniter interface for the Redis data store. This library tries to adhere 100% to the [defined protocol](http://redis.io/topics/protocol), including responses.

NOTE: This library is still under heavy development. Things may change in future releases.

Requirements
------------
1. PHP 5+
2. [CodeIgniter 2.0+](http://codeigniter.com)
3. A [Redis server](http://redis.io) compatible with the unified request protocol (Redis 1.2+)

Spark
-------------
This library is also released as a [Spark](http://getsparks.org). If you use this library in any other way, **don't copy the autoload.php to your config directory**.

Documentation
-------------

### Configuration
This library expects a configuration file to function correctly. A template for this file is provided with the library. 

### Generic command
You can execute any command using the command() method, just like you're using [redis-cli](http://code.google.com/p/redis/wiki/RedisCLI).

    $this->redis->command('PING');

This library also support PHP's [overloading](http://php.net/manual/en/language.oop5.overloading.php) functionality. This means you can call undefined methods, which are then dynamically created for you. These calls are routed to the generic __call() method. Undefined methods take as much arguments as you please.

    $this->redis->hmset('foohash foofield1 "bar1" foofield2 "bar2"');

### Keys

Set a new key with a value

    $this->redis->set('foo', 'bar');

Get a value by its key

    $this->redis->get('foo');
    
Delete a key/keys

	$this->redis->del('foo');
	
You can also pass a list of keys to be deleted as an array, space separated list or comma separated list. The space method is according to the defined protocol.

	$this->redis->del(array('foo', 'foo2'));
	$this->redis->del('foo foo2));
	$this->redis->del('foo, foo2));
	
Contributing
------------
I am a firm believer of social coding, so <strike>if</strike> when you find a bug, please fork my code on GitHub (http://github.com/joelcox/codeigniter-redis) and squash it. I will be happy to merge it back in to the code base (and add you to the "Thanks to" section). If you're not too comfortable using Git or messing with the inner workings of this library, please open a new issue (http://github.com/joelcox/codeigniter-redis/issues). 

License
-------
This library is released under the MIT license.

Thanks to
---------
* ysbaddaden for the idea of [splitting the different responses](https://github.com/ysbaddaden/php5-redis/blob/master/lib/Redis/Client.php) in his `read_raw_reply()` method.
* [Lucas Nolte](http://91media.de/) for filing bug reports and submitting patches.