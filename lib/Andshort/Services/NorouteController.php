<?php

require_once('Abstract_Service.php');

class NorouteController extends Abstract_Service{
		protected $__name = 'noroute';
		
public function __construct(){
	$this->_request=new Control_Request;
	$this->_params = $this->_request->getParams();
	$this->_session = Control_SessionManager::getInstance();			
	$this->shortener = new Shortener;
}
		
public function defaultAction(){

	Depends('Models::User');	
	Depends('Models::Url');			
	Depends('Models::Tag');
	Depends('View::TagCloudMaker');			
	
	
	$template = new Smarty();
	
	$uid = trim(str_replace('/','',$_SERVER['REQUEST_URI']));
	//settype($uid,'string');
	
	$Uid = new Url();
	$Uidres = $Uid->findByShorted($uid);
	
	//print_r($Uidres);
	
	if($Uidres instanceof UrlRow):
	
		if(isset($_SERVER['REMOTE_ADDR']) && $Uidres['lastVisitedIp']!=$_SERVER['REMOTE_ADDR']):
			//$Uidres->lastVisitedIp = $_SERVER['REMOTE_ADDR'];			
			$Uid->trackview($Uidres);
			$Uid->incrementVisits($Uidres);			
		endif;
		
		$unique = $Uidres->id;
		//$Uidres->id = base64_encode($unique);
		$Uidres->id = Shortener::getIdByShort($uid);
		
		$template->assign($Uidres->asArray());
		
		$Tag = new Tag();
		$clouds   = $Tag->getRelatedTags($unique);
	
	//echo '<!--'.print_r($Uidres,true).'-->';
	
	//exists
	if(isset($Uidres['userid'])):
		$U = new User();
		$User = $U->find($Uidres['userid']);
			
			//print_r($Uidres);
			//print_r($User);
			//die($Uidres['userid']);
			
			//-------------<check for the owner and the preferences
			if($User instanceof UserRow):
				$P = new Preference($User);
				$Pf = $P->fetchAll();
				//print_r($Pf);die;
				
				if($Pf->has('use_iframe')):
					//implicit redirect
					$this->redirect($Uidres->originalUrl);
				else:
					$this->redirect($Uidres->originalUrl);
					//read and show.
					//echo "?".$Uidres->originalUrl;
					$content = and_getUrlContents($Uidres->originalUrl);
					if(empty($content)):
						//continue ()
					else:
						//lets try to add the topbar
						if(preg_match('</html>',$content,$patterns)){
								$topBar = (sprintf('default/js/file/public|js|topbar.js/u/%s',$User->actkey));
								$replace = "<script src='http://".$_SERVER['HTTP_HOST']."/scripting/$topBar' type='text/javascript'></script></html>";
								$content = str_replace('</html>',$replace,$content);									
							die($content);
						}
					endif;
				endif;
				
			else:				
				
				$this->redirect($Uidres->originalUrl);
				
			endif;
			//-------------check for the owner and the preferences>
	endif;
		
	if(!empty($clouds)):
		$clouds = $clouds->asArray();
		$template->assign('keywords',implode(",",array_keys($clouds)));
	else:
		$template->assign('keywords',$Uidres->title);
	endif;
	
	//$this->result=$template->fetch('index/visited.html');
	$this->redirect($Uidres->originalUrl);
		
	
	else:
	
		$this->redirect('/');
	
	endif;
	
	
	
}
		
		public function response(){
			$this->defaultAction();
			return (string)$this->result;
		}
		
		
		public function redirect($url){
			header(sprintf("Location: %s",$url),true,301);
			exit;
		}
	}
?>