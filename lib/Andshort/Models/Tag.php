<?php
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

Depends('Db_Table');
Depends('Models::Row::TagRow');

class Tag2Uri extends Db_Table{
		
		protected $__tableName='tag2uris';
		protected $__rowClass='TagRow';
		protected $__primary='id';
		
		private $tag;
		private $uri;

		public function link(TagRow $tag,UrlRow $url){
		
			Depends('Models::Row::UrlRow');
			
			
			
			$this->tag = $tag;
			$this->uri = $url;
			
			$sql	= sprintf("SELECT * FROM ".$this->__tableName." WHERE tag_id=%d AND uri_id = %d",$tag->tag_id,$url->id);			
			$insert	= sprintf("INSERT IGNORE INTO ".$this->__tableName."(tag_id,uri_id,created) VALUES(%d,%d,NOW())",$tag->tag_id,$url->id);
			
			/*
			echo "<!--- \n ";
				//print_r(func_get_args());
				echo $sql."<br/>\n";
				echo $insert."<br/>\n";
			echo "--- >";
			*/
			
			$exists = $this->getDefaultAdapter()->fetch($sql);
			
			if(!$exists || !sizeof($exists)):			
				$this->getDefaultAdapter()->execute($insert);
			endif;

		}
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	
	

class Tag extends Db_Table{
	
		protected $__tableName='tagys';
		protected $__rowClass='TagRow';
		protected $__primary='tag_id';
		
		
		public function fetchLike($name,$limit=20){
			
			Depends('Models::List::TagList');
			$res = $this->fetchWhere("tag_text like '$name%' ",array($this->__primary,'tag_text'),$limit);
			
			if($res instanceof TagRow)
				return new TagList(array($res));
			elseif(is_array($res))
				return new TagList($res);
			else
				return NULL;
		}
		
		public function addUserTag(UrlRow $UrlRow,UserRow $u,$tag){
			
			$linker = new Tag2Uri();
			$TagRow = $this->fetchNewRow();
			
			$check = $this->getDefaultAdapter()->fetch(' SELECT * FROM tagys WHERE tag_text = "'.$tag.'" ');
			
			//print_r(func_get_args());
			//print_r($check);
			
			if(sizeof($check)):
				$TagRow->tag_id = (int)$check[0]->tag_id;
				$linker->link($TagRow,$UrlRow);
			else:
				$tag_text = ($tag instanceof TagRow) ? $tag->getName() : (string)$tag;
				$TagRow = $this->insert(array('tag_text'=>$tag_text,'userid'=>$u->getId(),'created'=>date('Y-m-d')));
				$linker->link($TagRow,$UrlRow);
			endif;
		}
		
		public function getPagedUrisByTag($tag,$page,$rowsPerPage=10){
		
			$totalResults=500;
			$rowsPerPage=floor($rowsPerPage);			
			
			$pages=floor($totalResults/$rowsPerPage);
			
			$maxRange = ($page<$pages?$page:$pages)*$rowsPerPage;
			$minRange = $maxRange-$rowsPerPage;
			
			$howMany = $rowsPerPage * $page;
			
			$SQL  = " SELECT U.* ";
			$SQL .= " FROM (SELECT uri_id FROM tagys t1 INNER JOIN tag2uris ON t1.tag_id = tag2uris.tag_id WHERE t1.tag_text = '$tag') AS t2p1";
			$SQL .= " INNER JOIN uris U on U.id = t2p1.uri_id";
			$SQL .= ' LIMIT '.$minRange.','.$rowsPerPage.' ';
			
			//echo $SQL;
			
			$res =  $this->fetch($SQL);
			
			if($res instanceof TagRow || is_object($res))
				return array($res->asArray());
			elseif(is_array($res))
				return $res;
			return NULL;
		
		}

			
		
		public function addTag(UrlRow $u,$t){
			
			if(is_string($t)):
				$t = new Tag;
				$TagRow = $t->insert(array('name'=>$t));
			elseif($t instanceof $TagRow):
				/**/
			endif;
			
			$this->getDefaultAdapter()->execute('INSERT IGNORE INTO tag2uris(uriid,tagid,created) VALUES("'.$u->id.'","'.$t->id.'",CURRENT_TIMESTAMP) ');
		}
		
		public function fetchRandomTags($n){
			$sql = sprintf("SELECT t1.tag_text AS name,tag2uris.uri_id as id FROM tagys t1 INNER JOIN tag2uris ON t1.tag_id = tag2uris.tag_id LIMIT %d",$n);
			return $this->fetch($sql);
		}
		
		public function getRelatedItems($tagname,$limited=50){
			Depends('Models::List::TagList');

$sql=<<<samp
	SELECT U.*
	FROM (SELECT uri_id FROM tagys t1 INNER JOIN tag2uris ON t1.tag_id = tag2uris.tag_id WHERE t1.tag_text = '$tagname' LIMIT $limited) AS t2p1
	INNER JOIN uris U on U.id = t2p1.uri_id
	LIMIT $limited;
samp;

	$res =  $this->fetch($sql);
	
	return $res;
}

public function getRelatedTags($URI,$limited=50){

Depends('Models::List::TagList');

$uri_id = ($URI instanceof UrlRow) ? $URI->id : $URI;

$sql=<<<samp
	SELECT t1.tag_text AS name,tag2uris.uri_id as id FROM tagys t1 INNER JOIN tag2uris ON t1.tag_id = tag2uris.tag_id AND tag2uris.uri_id = $uri_id LIMIT $limited;
samp;

	//echo $sql;
	
	$res =  $this->fetch($sql);
	
	if($res instanceof TagRow)
		return new TagList(array($res));
	elseif(is_array($res))
		return new TagList($res);
	else
		return NULL;
				
}		
/**
*The Typical Related Tags Query
• Get all tags related to a particular tag via an item
• The “reverse” of the related items query; we want a set 
of related tags, not related posts
*/
public function getRelatedTaggedTags(UrlRow $URI,$tag_text=50){
$sql=<<<sql
		SELECT t2p2.tag_id, t2.tag_text  
		FROM (SELECT post_id FROM Tags t1
		INNER JOIN tag2uris 
		ON t1.tag_id = tag2uris.tag_id
		WHERE t1.tag_text = $tag_text LIMIT 100
		) AS t2p1
		INNER JOIN tag2uris t2p2
		ON t2p1.post_id = t2p2.post_id
		INNER JOIN Tags t2
		ON t2p2.tag_id = t2.tag_id
		GROUP BY t2p2.tag_id LIMIT 100;
sql;
}
/**
*@Comment Problems With Related Items Query
	Joining small to medium sized “tag sets” works great
	But... when you've got a large tag set on either “side” of the join, problems can occur with scalablity
	One way to solve is via derived tables
*/
public function getRelatedUris(UrlRow $URI,$limit=50){
$sql = <<<samp
		SELECT p2.uri_id 
		FROM ( SELECT tag_id FROM tag2uris	WHERE uri_id = $URI->id LIMIT $limit ) AS p1
		INNER JOIN tag2uris p2
		ON p1.tag_id = p2.tag_id
		GROUP BY p2.uri_id LIMIT $limit;
samp;

}
		
}

?>