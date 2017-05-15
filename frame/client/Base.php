<?php

// 增加命名空间
namespace frame\client;

class Base {

	public $ip;
	public $key;
	public $port;
	public $data;
	public $timeout = 5;

    public function __construct($ip,$port,$data,$timeout){
        $this ->ip = $ip;
        $this ->port = $port;
        $this ->data = $data;
        $this ->timeout = $timeout;
    }

	public function send(){

	}

	public function setKey($key){
		$this ->key = $key;
	}

	public function getKey(){
		return $this ->key;
	}
}