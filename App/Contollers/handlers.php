<?php

//namespace App\lib;
//Define the handler
//use test\Tasks as Tasks;

class handlers
{
	private $index;
	private $user;

	private $task;
	private $meeting;

	public function __construct(){
		
	}
	public function sample($data,$callback){
		$callback(406,array('name'=>'sample handler'));
	}

	public function api($data,$callback){
		$this->api = new Api();
		$acceptableMethods = ['post','get','put','delete'];
		$method =$data["method"];
		if(array_search($method, $acceptableMethods) > -1){
			$this->api->$method($data,$callback);
		}else{
			$callback(405,NULL);
		}
	}

	public function test($data,$callback){
		$callback(406,array('name'=>'test handler'));
	}

	public function ping($data,$callback){
		$callback(200,NULL);
	}

	public function notfound($data,$callback){	
		$this->index = new Index();
		$acceptableMethods = ['post','get','put','delete'];
		$method =$data["method"];
		if(array_search($method, $acceptableMethods) > -1){
			$this->index->$method($data,$callback);
		}else{
			$callback(405,NULL);
		}
		/*//$callback(404,array('name'=>'You Have Reached Swoole Server'));
		//var_dump($data);
		$urlspilited = explode('/',$data["trimmedPath"]);
		
		if($urlspilited[0]=="Tasks"){
			$this->task = new Tasks();
			$acceptableMethods = ['post','get','put','delete'];
			$method =$data["method"];
			if(array_search($method, $acceptableMethods) > -1){
				//var_dump($data["method"]);
				//users->$data->method($data,$callback);
				//var_dump($this->user);
				$this->task->$method($data,$callback);
				//var_dump($data3);
				//$callback(202,$data3);
			}else{
				$callback(405,NULL);
			}
		} */
	}

	public function users($data,$callback){
		$this->user = new Users();
		$acceptableMethods = ['post','get','put','delete'];
		$method =$data["method"];
		if(array_search($method, $acceptableMethods) > -1){
			$this->user->$method($data,$callback);
		}else{
			$callback(405,NULL);
		}

	}
	public function book($data,$callback){
		$this->user = new Controller();
		$method ="invoke";
		if($method){
			//var_dump($data["method"]);
			//users->$data->method($data,$callback);
			//var_dump($this->user);
			$this->user->$method($data,$callback);
			//var_dump($data3);
			//$callback(202,$data3);
		}else{
			$callback(405,NULL);
		}

	}

	public function tasks($data,$callback){
		$this->task = new Tasks();
		$acceptableMethods = ['post','get','put','delete'];
		$method =$data["method"];
		if(array_search($method, $acceptableMethods) > -1){
			//var_dump($data["method"]);
			//users->$data->method($data,$callback);
			//var_dump($this->user);
			$this->task->$method($data,$callback);
			//var_dump($data3);
			//$callback(202,$data3);
		}else{
			$callback(405,NULL);
		}

	}
	public function meetings($data,$callback){
		$this->meeting = new Meetings();
		$acceptableMethods = ['post','get','put','delete'];
		$method =$data["method"];
		if(array_search($method, $acceptableMethods) > -1){
			//var_dump($data["method"]);
			//users->$data->method($data,$callback);
			//var_dump($this->user);
			$this->meeting->$method($data,$callback);
			//var_dump($data3);
			//$callback(202,$data3);
		}else{
			$callback(405,NULL);
		}

	}

}

