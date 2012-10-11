<?php

namespace Andshort\Models;

class Url extends \ActiveRecord\Model
{
	// explicit table name since our table is not "books"
	static $table_name = 'uris';

	// explicit pk since our pk is not "id"
	static $primary_key = 'uri_id';

	// explicit connection name since we always want production with this model
	static $connection = 'production';

	// explicit database name will generate sql like so => db.table_name
	//static $db = 'test';
	
	public static function shortExists($short_url){
		return self::all(array('conditions' => sprintf('short_url = "%s"', $short_url))) != NULL;
	}
}
?>