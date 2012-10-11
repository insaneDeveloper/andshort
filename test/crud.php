	<?php	
	
	require_once '../lib/Andshort/Bootstrap.php';
	
	error_reporting(E_ALL^E_WARNING);
	ini_set('display_errors', 'On');
	
	echo 'Testing library... <br />';
	
	ActiveRecord\Cache::flush();
	
	//first of all load the config
	Andshort\Config::setup( require('../config.php') );	
	
	//instance it
	$Sh = new Andshort\Shortener();	
	
	assert(!$Sh->constraint->shortExists('andshhhh'));
	
	$row = $Sh->short('http://www.google.com/');
	
	assert($row != NULL);
?>