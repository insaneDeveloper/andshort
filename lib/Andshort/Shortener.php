<?php
namespace Andshort;

require_once 'Bootstrap.php';

use Builders\B64;

class Shortener {
	
	//Constraint
	public $constraint;
	
	public $builder;
	
	public function __construct(Andshort\Builder $builder = null){
		
		if(!$this->builder)
			//$this->builder = new Builders\B64();
			$this->builder = new Builders\ABC();
			
		$this->constraint = new Constraint();
	}
	
	public function short($url, array $parameters = array()){
		
		$short = $this->builder->create($url, $parameters);
		
		return $short;
		
	}
}
?>