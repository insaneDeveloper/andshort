<?php
require_once('Abstract_Service.php');


class GuiController extends Abstract_Service{
		
		protected $__name = 'gui';
		
		public function tagAction(){
			
			Depends('View::TagCloudMaker');
			Depends('Models::Tag');
			Depends('Models::Url');
			
			$Tag      = new Tag;
				
			$ranks    = $Tag->getDefaultAdapter()->fetch(sprintf(Sql_Logic::TOP10URL));        
			$related   = $Tag->getRelatedItems($this->args['tag']);
			
			$pages = 5;
			$params=$this->request->getParams();
			
			$this->renderView = false;
			$this->resultFormat  = 'json_encode';
			
			if($this->request->hasNoEmpty('page') && $this->request->hasNoEmpty('tag')):
				
				$page=(int)$this->params->page;
				
				if($this->request->hasNoEmpty('resPerPage'))
					$pages=(int)$this->params->resPerPage;
				
				if(!is_numeric($page))
					$page = 1;
				
				$Url = new Url;
				$this->result = (array) $Tag->getPagedUrisByTag($this->params->tag,$page,$pages);
				
			endif;
		}
		/**
		*
		*/
		public function rankingAction(){
			
			Depends('View::TagCloudMaker');
			Depends('Models::Tag');
			Depends('Models::Url');			
			
			$pages = 5;
			$params=$this->request->getParams();
			
			$this->renderView = false;
			$this->resultFormat  = 'json_encode';
			
			if($this->request->hasNoEmpty('page')):
				
				$page=(int)$this->params->page;
				
				if($this->request->hasNoEmpty('resPerPage'))
					$pages=(int)$this->params->resPerPage;
				
				if(!is_numeric($page))
					$page = 1;
				
				$Url = new Url;
				$this->result = (array) array_map(array($this,'attachTagElements'),$Url->getRank($page,$pages));
				
			endif;
			
		}
		
		private function attachTagElements($element){
			$Tag = new Tag;
			$relatedTags  = $Tag->getRelatedTags($element->id);
			
			if($relatedTags instanceof TagList && !$relatedTags->isEmpty()):
				$element->tags = (array) array_keys($relatedTags->asArray());
			endif;
			
			return $element;
		}
	}
?>