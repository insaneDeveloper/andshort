<?php

require_once('Abstract_Service.php');

class BindController extends Abstract_Service{
	protected $__name = 'bind';
	
	public function __construct(){
		$this->renderView = false;
	}

	public function defaultAction(){
		if(isset($_SERVER['HTTP_REFERER'])):
			echo $_SERVER['HTTP_REFERER'];
		endif;
	}
}
		
?>