<?php
namespace Andshort;

use Models\Url;

class Constraint {
	
	public function shortExists($uid){
		return Models\Url::shortExists($uid);
	}
}