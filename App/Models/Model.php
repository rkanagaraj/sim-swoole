<?php

$GLOBALS['site_title'] = "Testing site";
$GLOBALS['hello_world'] = "Hello World..!!!";

class Model
{
	private $db;
	private $server;
	private $swoole_mysql;
	private $nid;
	private $gettotal;
	private $redisdb;
	
	Public function __construct(){
		$this->redisdb =  new Swoole\Coroutine\Redis();
		$this->redisdb->connect('192.168.5.203', 6379);
	}
	

	public function redis_set($key,$value){
		return $this->redisdb->set($key, $value);
	}

	public function redis_hset($key,$field,$value){
		return $this->redisdb->hset($key, $field,$value);
	}

	public function redis_hget($key,$field){
		return $this->redisdb->hget($key, $field);
	}

	public function redis_hmset($key,$value = null){
		return $this->redisdb->hMSet($key,$value);
	}

	public function redis_hmget($key,$field){
		return   $this->redisdb->HMGET($key,[$field]);
		//var_dump($result);
	}

	public function redis_hdel($key,$field){
		return   $this->redisdb->HDEL($key,$field);
	}

	public function redis_hgetall($key){
		return   $this->redisdb->hgetall($key);
	}

	public function redis_exists($key){
		return   $this->redisdb->exists($key);
		var_dump($result);
	}

	public function redis_get($key){
		return $this->redisdb->hget($key);
	}

	public function expire($key){
		return $this->redisdb->EXPIRE($key,60*60*60*24);
	}

	public function redis_getallkeys(){
		return $this->redisdb->keys("*");
	}

}