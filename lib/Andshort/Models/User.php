<?php

namespace Andshort\Models;

class User extends \ActiveRecord\Model
{
	// explicit table name since our table is not "books"
	static $table_name = 'users';

	// explicit pk since our pk is not "id"
	static $primary_key = 'user_id';

	// explicit connection name since we always want production with this model
	static $connection = 'production';

	// explicit database name will generate sql like so => db.table_name
	//static $db = 'test';
}
?>