<?php
namespace Andshort\Builders;

use Models;

/**
*
* This is a basic example of a Builder used by Shortener
* it converts an integer into an encoded version of it using alphabet`s letters
*/
class ABC extends \Andshort\Builder {
	
	const name = 'ABC';
	
	protected function uid($uri){
		
		if(!is_numeric($uri))
			throw new \Andshort\Exception(__CLASS__.' can only encode int values '.gettype($uri));
			
		$bin = decbin(((int)$uri) << 2);
		$pos = 0;
		$alphas = range('a', 'z');
		
		$uid = preg_replace_callback('/[0-9]/im', 
			function($match) use ($pos, $alphas) { $pos++; return $alphas[$match[0]]; },
			$bin);
			
		return $uid;
		
	}
}

?>