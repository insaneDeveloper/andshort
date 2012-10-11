<?php

require_once('Abstract_Service.php');



class UnitTestingController extends Abstract_Service{
	
	public $renderView=false;
	
	public static function defaultAction (){}
	
	public static function testTaggersAction(){
		
		Depends('Models::User');
		Depends('Models::Url');
		Depends('Models::Tag');
		
		$Url = new Url;
		$shortener = new Shortener();
		
		
		$UserRow = new UserRow();
		$UserRow->id=18;
		
		$Tag = new Tag;
		$TagRow = new TagRow;
		$UrlRow = new UrlRow;
		$UrlRow->userid = 13;
		$UrlRow->originalUrl = 'http://www.google.com';
		
		$result = $shortener->doShort($UrlRow,NULL)->result();
		
		$TagRow->setName('unittesting'.date('ymdhis'));		
		
		$tags = split('[/ ]','Google/Code/CodeJam/2009/Googleadores');
		//print_r($tags);
		foreach($tags as $t):
			$TagRow->setName((string)$t);
			$Tag->addUserTag($result,$UserRow,$TagRow);
		endforeach;					
		$Tag->getDefaultAdapter()->execute(sprintf(" DELETE FROM uris WHERE shortUrl = '%s'",$result['shortUrl']));
		
	}
}
	