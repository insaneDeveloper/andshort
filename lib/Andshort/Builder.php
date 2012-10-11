<?php
namespace Andshort;

/**
*
* 
*/
abstract class Builder{
	
	const name = 'undefined';
	
	protected function uid($uri){
		// implement
	}
	
	function create($uri, array $data = array()){
		
		$instance = get_called_class();
		$strategy = $instance::name;
		
		$attributes = array('original_url' => $uri, 'strategy' => $strategy);
			
		if(!empty($data))
			$attributes = array_merge($attributes, $data);			
		
		//exchange array
		
		$attributes['created_date'] = !isset($attributes['created_date']) ? $attributes['created_date'] = date('Y-m-d') : $attributes['created_date'];
		
		$attributes['created_time'] = !isset($attributes['created_time']) ? $attributes['created_date'] = date('h:i:s') : $attributes['created_time'];
			
		$url = new \Andshort\Models\Url($attributes);
		$url->save();
		
		//add the short_url
		$url->short_url = $this->uid($url->uri_id);
		
		// and save
		return $url->save();
		
	}
		
}