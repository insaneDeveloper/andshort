<?php	

Depends('Db_Table');
Depends('Models::Row::CategorieRow');	

class Categorie extends Db_Table {

    public static $TIE_USER_URL_CATEGORY="insert into cat2uris(uri_id,cat_id) VALUES('%d','%d')";
	public static $CREATE_CATEGORY="insert ignore into categories(name,userid,public,created) VALUES('%s','%d','%d','%s')";

    protected $__tableName='categories';
    protected $__rowClass='CategorieRow';

	public function create($name,$user_id,$public=0){
		$sq = sprintf(self::$CREATE_CATEGORY,$name,$user_id,$public,date('Y-m-d'));
		$this->getDefaultAdapter()->execute($sq);
		
		$cat = $this->getDefaultAdapter()->fetch(sprintf('SELECT id from categories where name="%s" and userid="%d" limit 1',$name,$user_id));
		return $cat[0]->id;
	}
    /**
     * Binds a url to category
     */
    public function bindCategory($url,$cat) {
        $sql = sprintf(self::$TIE_USER_URL_CATEGORY,$url,$cat);
        return $this->getDefaultAdapter()->execute($sql);
    }

    /**
     *
     * @param UserRow $user
     * @return CategoryList
     */
    public function _getUserCategories(UserRow $user) {
        $res = $this->fetchWhere("userid='".$user->id."'",array('id','name',"IF(public=1,'public','private') as 'visibility'"));

        Depends("Models::List::TagList");

        if($res instanceof CategorieRow)
            return new CategoryList(array($res));
        elseif(is_array($res))
            return new CategoryList($res);
        else
            return NULL;
    }

    /**
     *
     * @param UserRow $user
     * @return CategoryList
     */
    public function getUserCategories(UserRow $user) {

        Depends("Models::List::TagList");

        $FETCH_CATEGORIES_BY_UID  ="SELECT DISTINCT CA.name, IFNULL(COUNT( CU.uri_id ),0) AS 'countUris' ";
        $FETCH_CATEGORIES_BY_UID .=", CA.id AS 'cat_id', IF( CA.public =1, 'public', 'private' ) AS 'visibility' ";
        $FETCH_CATEGORIES_BY_UID .="FROM categories CA LEFT JOIN cat2uris CU ON CU.cat_id=CA.id ";
        $FETCH_CATEGORIES_BY_UID .="WHERE userid = %d GROUP BY CA.id ";

        $FETCH_CATEGORIES_BY_UID .="UNION SELECT CA2.name, COUNT( CA2.id ) AS 'countUris', CA2.id AS 'cat_id', IF( CA2.public =1, 'public', 'private' ) AS 'visibility' ";
        $FETCH_CATEGORIES_BY_UID .="FROM categories CA2 ";
        $FETCH_CATEGORIES_BY_UID .="WHERE CA2.global =1	GROUP BY CA2.id";

        $res = $this->getDefaultAdapter()->fetch(
            sprintf($FETCH_CATEGORIES_BY_UID,$user->id)
        );

        if($res instanceof CategorieRow)
            return new CategoryList(array($res));
        elseif(is_array($res))
            return new CategoryList($res);
        else
            return NULL;
    }
    /**
     *
     * @param UserRow $user
     * @return CategoryList 
     */
    public function getUserCategorizedUrls(UserRow $user) {
        $sql = "SELECT
					
					IFNULL(cate.name,'') as 'category'
					,cate.id as 'categoryId'
					,uris.originalUrl
					,uris.shortUrl
					,uris.visitedTimes
					,uris.createdDate
					,IF(uris.public=1,'public','private') as 'visibility'
					
					FROM uris
					LEFT JOIN cat2uris cat ON cat.uri_id = uris.id
					LEFT JOIN categories cate ON cate.id = cat.cat_id OR cate.global =1
					WHERE uris.userid = ".$user->id." ORDER BY cate.name DESC";

        $res = $this->fetch($sql);

        Depends("Models::List::TagList");

        if($res instanceof CategorieRow)
            return new CategoryList(array($res));
        elseif(is_array($res))
            return new CategoryList($res);
        else
            return NULL;
    }
}
?>