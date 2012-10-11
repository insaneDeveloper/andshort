<?php
require_once('Abstract_Service.php');

class QuickController extends Abstract_Service{
	
		public $shortener;
		protected $__name = 'quick';
		public $view_class = 'Smarty';
		
		
		/**
		* defaultAction
		*/
		public function tagsAction(){
			Depends('Models::Tag');
			Depends('Models::Row::TagRow');
			
			$this->renderView=false;
			$this->resultFormat = 'json_encode';
			$this->the_result = array();
			$Tag = new Tag();
			
			if($this->request->hasNoEmpty('q')):
				$name = $this->params->q;
				$populars = $Tag->fetchLike($name);
				if($populars){
					//print_r($populars);
					//print_r($populars->asArray());
					$this->the_result = $populars->asArray();
				}				
			endif;
		}
		/**
		* defaultAction
		*/
		public function defaultAction(){
		
			$this->shortener = new Shortener();
			
			Depends('Models::Categorie');
			Depends('Models::User');
			Depends('Models::Row::UserRow');
			Depends('Models::Row::UrlRow');
			
			$user = new User;
			$userRow = $user->fetchNewRow();
			$UrlRow = new UrlRow(new Url());
			
			$this->view = new Smarty();
			$this->renderView=true;
			
			
			$url = ($this->request->hasNoEmpty('shortThis') || $this->request->hasNoEmpty('url')) ? 
									empty($this->params->shortThis) ? addslashes($this->params->url) : addslashes($this->params->shortThis)  : "";
			
			$metaInfo = getUrlData($url);
			
			$params = array(
				"title" => $this->request->hasNoEmpty('title') ? addslashes($this->params->title) : (is_array($metaInfo) && $metaInfo['title']!=null ? addslashes($metaInfo['title']) : '')
				,"passwordProtected" => $this->request->hasNoEmpty('password') ? addslashes($this->params->password) : ""
				,"originalUrl" => $url				
				,"createdDate" => date('Y-m-d')
				,"createdTime" => date('h:i:s')
				,"userid" => isset($this->session->currentUser) && ($this->session->currentUser instanceof UserRow) ? $this->session->currentUser->id : ""
			);
			
			$UrlRow->setFromArray(	$params	);
			
			if(  !empty($UrlRow['originalUrl']) ){				
				
				//********************************************This short has an owner ? 
				if($this->request->hasNoEmpty('secretKey')):					
					
					$userRow->secretKey = $this->params->secretKey;				
					$userResult = $user->findBySecretKey($userRow);
					
					$result = $this->shortener->doShort($UrlRow,$userResult)->result();					
				else:
					$result = $this->shortener->doShort($UrlRow)->result();
				endif; //</ end secretKey>
				
				unset($result->createdDate,$result->createdTime,$result->userid);
				$to = 'http://'.$_SERVER['HTTP_HOST'].'/'.$result->shortUrl;
				$result->shorty = $to;
				$this->view->assign('shorten',$to);
				
				//********************************************this short has Categorie ?
				if($this->request->hasNoEmpty('categorie'))
					$this->shortener->bindCategorie($result,$this->params->categorie);
					//</ end categorie>
					
				if($this->request->hasNoEmpty('tags'))
					$this->shortener->setTags($result,explode(",",$this->params->tags));
					//</ end password>				
				
				if($this->request->hasNoEmpty('tweetMessage')	&& is_a(@$this->session->currentUser,'UserRow')):
				
					//Twitter
					Depends("Request::SimpleTwitter");
					$SimpleTwitter = new SimpleTwitter($this->session->currentUser);
					
					$strMessage = $this->request->hasNoEmpty('tweetMessageText') ? addslashes($this->params->tweetMessageText) : $to;
					
					if($SimpleTwitter->canTwit()):
						$message = $SimpleTwitter->tweet("$strMessage");
						$result->twitterStatusId = $message->ID;
					else:
						//echo "!canTwit";
					endif;					
					
				endif;
				
				$this->view->assign('shorten',$result->shorty);
				
				
				if($this->request->hasNoEmpty('resultFormat')):
				
					$resultFormat = $this->params->resultFormat;
					
					$this->renderView=false;
					$this->result = $result->asArray();
					
					switch($resultFormat):
						case 'json':
						$this->resultFormat = 'json_encode';						
						break;
						
						case 'simple':
						$this->resultFormat = 'json_encode';
						$this->result = $this->result['shorty'];
						
						die($this->result);
						break;
						
					endswitch;
					
					return;
					
				else:
				
					//if(!empty($params['userid']))
					//$this->view->AssignArray($this->session->currentUser);
				endif;//</ end resultFormat>
				
				//print_r($this);
				
			}else{
				$this->view->assign('shorten',"");
			}//</ end url>
			
			die($this->view->fetch('index/redirecting.html'));
			
		}	

	}
?>