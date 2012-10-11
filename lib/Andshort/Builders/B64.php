<?php
namespace Andshort\Builders;

use Models;

/**
*
* This is a basic example of a Builder used by Shortener
* it uses base64_encode for encoding the ID generated for the URL
* you can of course change this by modyfing the <uid> method
*/
class B64 extends \Andshort\Builder {
	
	const name = 'B64';
	
	protected function uid($uri){
		
		return base64_encode($uri);
		
	}
	
	protected function create($uri, array $data = array()){	
		return parent::create($uri, $data);
	}
}

?>