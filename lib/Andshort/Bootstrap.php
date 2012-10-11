<?php

require_once "../vendor/autoload.php";

//change to wherever the path of php-activerecord is
require_once "../vendor/php-activerecord/php-activerecord/ActiveRecord.php";

date_default_timezone_set( 'America/Chicago' );

if (!function_exists('andshort_autoload')):
	
	set_include_path(implode(PATH_SEPARATOR,
	array(get_include_path()
	, './'
	, '../lib'
	, '../vendor/')));
	
	function andshort_autoload($class_name)
	{
		$path = str_replace('\\', '/', $class_name);
		
		foreach(explode(PATH_SEPARATOR, get_include_path()) as $dir):
			
			$file = $dir. '/' . $path .'.php';
			
			($exists = file_exists($file)) and require $file;
			
			if($exists)
				break;
		
		endforeach;
		
	}
	
	spl_autoload_register('andshort_autoload');
	
endif;
?>
