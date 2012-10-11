<?php
namespace Andshort;

use ActiveRecord;

/**
*
* This is the default Config class
*/
class Config{
		
	public static $current;
	
	public static function setup(array $config){
		
		self::$current = $config;
		
		\ActiveRecord\Config::initialize(function($cfg)
		{
			$cfg->set_model_directory(__DIR__.'/Models');
			$cfg->set_connections( \Andshort\Config::$current['db'] );
		});	
		
	}
		
}