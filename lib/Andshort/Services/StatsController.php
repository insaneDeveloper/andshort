<?php
require_once('Abstract_Service.php');
class StatsController extends Abstract_Service{
	
	protected $__name="stats";
	
	/**
	*
	*/
	public function profile_statsAction(){
		$this->fuckOffNotLogged();
		$this->view->assign('links',$this->__defStats());
	}
	public function mystatsAction(){
		$this->profile_statsAction();
	}
	
	public function profile_stats_agentsAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_agents_linkAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_linkAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_timelineAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_timeline_linkAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_localeAction(){
		
		$this->profile_statsAction();
		$user = $this->getCurrentUser();
		
		Depends("Models::UrlAccess");
		
		$UrlAccess = new UrlAccess();
		$xmlnodes = $UrlAccess->fetchAccessByUserId($user['id']);
		$xmls = "";
		
		foreach($xmlnodes as $node):
			$xmls.=sprintf('<set label="%s" value="%d" isSliced="0" />',$node->country_code,$node->count);
		endforeach;
		
		$xml = sprintf('<chart caption="%s" palette="4" decimals="0" enableSmartLabels="1" enableRotation="0" bgColor="EFEFEF" bgAlpha="0" bgRatio="0" showBorder="0" startingAngle="70">%s</chart>',
					   'Promedio de visitas totales por Pais'
					   ,$xmls);
		
		$this->view->assign('xmldata',$xml);
	}
	public function profile_stats_locate_linkAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_plaAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_tagsAction(){
		$this->profile_statsAction();
	}
	public function profile_stats_topAction(){
		$this->profile_statsAction();
	}
	
	
	
	/**
	*
	*/
	public function __defStats(){
	
		Depends("Models::Url");
		
		$Url = new Url();		
		$uris = $Url->fetchAll(sprintf('userid=%d',$this->session->currentUser->id));
		$response=array('posted'=>0,'shared'=>0,'last_visit'=>0,'total_hits'=>0,'most_visited'=>NULL);
		
		//posted,shared,last_visit,total_hits
		
		while ($c = current($uris)):
			$response['posted']++;
			
			if($c->public==1):
				$response['shared']++;
			else:
				//$response['shared']++;
			endif;
			$response['total_hits']+=$c->visitedTimes;
			$response['last_visit']=$c->last_visit;
			
			if($response['most_visited']<$c->visitedTimes)
				$response['most_visited'] = $c->shortUrl;
			
			next($uris);
			
		endwhile;
		reset($uris);
		
		return $response;
	}
	
	private function fuckOffNotLogged() {
        if (!$this->_userLogged()):
            header("Location: /", 404);
            exit;
    endif;
	}
	
	/**
     * @private
     */
    private function _userLogged() {
        global $_SESSION;
        return array_key_exists('currentUser', $_SESSION) && !empty($_SESSION['currentUser']);
    }
	
	private function getCurrentUser(){
		return $_SESSION['currentUser'];
	}
}
?>