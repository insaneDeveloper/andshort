<?php
require_once('Abstract_Service.php');
class ShareController extends Abstract_Service {
    /**
     *
     * @var <type>
     */
    public $request;
    /**
     *
     * @var <type>
     */
    public $params;
    /**
     *
     * @var <type>
     */
    public $useDefaultService=true;	
	public $renderView=false;
	
	protected $__name = 'share';
	
	/**
	*
	*/
	public function twitterAction(){
		
		$params = $this->request->getParams();
		$args = $this->args;
		$session  = Control_SessionManager::getInstance();
		
		
		if(!empty($params->message)
		 && isset($session->currentUser) 
		 && $session->currentUser instanceof UserRow):
		 
		$this->resultFormat='json_encode';
		
		Depends("Request::SimpleTwitter");
		//######################Twitter######################
		$simpleTwitter = new SimpleTwitter($session->currentUser);
		$message  = $params->message;
		
		if($simpleTwitter->canTwit()){
			$tweet = $simpleTwitter->tweet(addslashes(urldecode($message)));
			$this->result = $tweet;
		}
						
			
		endif;
	}
}