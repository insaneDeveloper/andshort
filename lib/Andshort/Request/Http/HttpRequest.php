<?php
	

class Http_Request{

	public $method;
	public $getParams;
	public $postParams;
	public $params;
	
	public $language;
	
	public function __construct(){
		global $_SERVER;
		$this->method = ucfirst($_SERVER['REQUEST_METHOD']);
		$this->getParams = (object)$_GET;
		$this->postParams = (object)$_POST;
		$this->params = (object)$_REQUEST;
	}
	
	public function getMethod(){
		return $this->method;
	}
	
	}
?>