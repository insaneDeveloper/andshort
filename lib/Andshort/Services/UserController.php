<?php

require_once('Abstract_Service.php');



class UserController extends Abstract_Service{
	
	public $renderView=false;
	protected $__name = 'user';
		
	/**
	* default handler for login action
	*/
	public function loginAction(){
	
		@Depends('Models::User');
		
		$this->renderView=false;
		$this->resultFormat='json_encode';
		
		$params=$this->request->getParams();

		$User = new User();				
		$userRow = $User->fetchNewRow();
			
		if($this->request->hasNoEmpty('user') && $this->request->hasNoEmpty('pass')):
			
			$userRow->email = $params->user;
			$userRow->password = $params->pass;
			$userRow = $User->checkLogin($userRow,array('username','id','secretKey'));
			
			if($userRow){
				//print_r($userRow);	
				$socialData = $User->getSocialInfo($userRow);
				//while($s = current($loginResult)):
				//	$result['social_'.$s['uri_type']]= $s['uri'];
				//	next($loginResult);
				//endwhile;
				$userRow->userid = $userRow->id;
				$this->session->set('currentUser',$userRow);
			}
		
		endif;
		
		$this->result = $userRow;
	}
	/**
	*
	*/
	public function public_profileAction(){
		
		global $_SERVER;
		
		//print_r($_SERVER);
		
		Depends("Models::User");
		
		$User = new User;		
		
		$REDIRECT_QUERY_STRING = $_SERVER['REDIRECT_QUERY_STRING'];
		parse_str($REDIRECT_QUERY_STRING,$uriv);
		
		if(!empty($REDIRECT_QUERY_STRING) && array_key_exists('userProfile',$uriv)):
		
			$this->renderView=true;			
			$this->view = new View_Template('view/publicProfile');
			
			$username = $uriv['userProfile'];
			$params = $this->params;
			$user = $User->usernameExists($username);			
			
			//invalid username
			if(!($user instanceof UserRow)):
				header("Location: /",true,301);
				exit;
			endif;
			
			$P = new Preference($user);
			$preferences = $P->fetchAll();
			
			
			
			//TagCloud
			
			Depends('View::TagCloudMaker');
			Depends('Models::Tag');
			Depends('Models::Url');
			
			$Tag      = new Tag;			
			$ranks    = $Tag->getDefaultAdapter()->fetch(sprintf(Sql_Logic::TOP10URL));        
			$clouds   = $Tag->fetchRandomTags(30);
			
			$clouder  = new Visual_TagCloudMaker($clouds, array(
				'st-tags t1',
				'st-tags t2',
				'st-tags t3'
			));
			
			$tagcloud = $clouder->tagcloud('getName');			
			$this->view->loop('tagcloud', $tagcloud);
			
			//Social links
			
			$this->view->AssignArray($user->asArray());
			
			$this->view->assign('social_twitter',$preferences->has('twitter'));
			$this->view->assign('social_facebook',$preferences->has('facebook'));
			$this->view->assign('social_delicious',$preferences->has('delicious'));
			$this->view->assign('social_flickr',$preferences->has('flickr'));
			$this->view->assign('social_lastfm',$preferences->has('lastfm'));
			$this->view->assign('social_linkedin',$preferences->has('linkedin'));
			$this->view->assign('social_tumblr',$preferences->has('tumblr'));
			$this->view->assign('social_youtube',$preferences->has('youtube'));
			
			
			$this->view->assign('mainScript','/public/js/'.$this->getControllerName().'/main.js');
		
			
		endif;
	}
	/**
	*
	*/
	public function categoriesAction(){
		
		Depends('Models::User');
		Depends('Models::Categorie');

		
		$this->renderView=false;
		$this->resultFormat = 'json_encode';
		$this->result = '';
					
		$params=$this->request->getParams();
		$UserM = new User;
		$userRow = $UserM->fetchNewRow();
		
		if($this->request->hasNoEmpty('secretKey')):
		
			$userRow->secretKey = $params->secretKey;
			$user = $UserM->findBySecretKey($userRow);
			
			if(is_a($user,'UserRow')):
				
				$Categorie = new Categorie;
				
				if(!$this->request->hasNoEmpty('withPages'))
					$this->result = $Categorie->getUserCategories($user);
				else
					$this->result = $Categorie->getUserCategorizedUrls($user);
					
					$this->result = $this->result->asArrayList();
					
					if(!is_array($this->result)) $this->result = array();
					
					//print_r($this->result);die;
			endif;
			
		endif;
	}
	
	/**
	*
	*/
	public function preferencesAction(){
		
		$params=$this->request->getParams();
		$UserM = new User;
		$userRow = $UserM->fetchNewRow();
		
		$this->renderView=false;
		$this->resultFormat = 'json_encode';
		
		if($this->request->hasNoEmpty('key')):
			$userRow->actKey = addslashes($params->key);
			$user = $UserM->findByActKey($userRow);
			
			if($user instanceof UserRow):
				$this->result = $UserM->getSocialInfo($user,array(
					'facebook'
					,'twitter'
					,'adsense_id'
					,'analytics_id'
				));
			endif;
			
		endif;
		
	}
}
?>