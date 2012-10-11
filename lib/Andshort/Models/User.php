<?php
Depends('Db_Table');
Depends('Models::Row::UserRow');

/**
 * 
 */
class User extends Db_Table {

    protected $__tableName='users';
    protected $__rowClass='UserRow';
    protected $__primary='id';

    private static $GET_USER_SOCIAL_AND_PREFERENCES='SELECT type,content from user_places WHERE userid = "%d" ';
    /**
     *
     *
     * @param UserRow $user
     */
    public function updateLastActivity(UserRow $user) {
        $this->getDefaultAdapter()->execute(
            sprintf("UPDATE ".$this->name()." SET last_activity_ip='%s',last_activity='%s'",$user->id,$_SERVER['REMOTE_ADDR'])
        );
    }
    /**
     * Return the login result based on username and password
     */
    public function checkLogin(UserRow $user,array $cols=array('*')) {
        if(strpos($user->email,"@")>-1):
        //echo "email";
            return $this->fetchWhere(array("email='".$user->email."'","password='".$user->password."'"),$cols,1);
        else:
        //echo "username";
            return $this->fetchWhere(array("username='".$user->email."'","password='".$user->password."'"),$cols,1);
    endif;
    }

    /**
     *
     * @param UserRow $user
     * @return <type>
     */
    public function getPassword(UserRow $user) {
        return $this->fetchWhere(array("email='".$user->email."'"),array('password'));
    }

    /**
     *
     * @param UserRow $user
     * @return <type>
     */
    public function findByEmail(UserRow $user) {
        return $this->fetchWhere(array("email='".$user->email."'"),array('*'));
    }

    /**
     * Return the login result based on secret key
     */
    public function findBySecretKey(UserRow $user,array $cols=array()) {
        return $this->fetchWhere(array("secretKey='".$user->secretKey."'"),$cols);
    }
	
	/**
     * Return the login result based on activate key
     */
    public function findByActKey(UserRow $user,array $cols=array()) {
        return $this->fetchWhere(array("actKey='".$user->actKey."'"),$cols);
    }

    /**
     *
     */
    public function getSocialInfo(UserRow $user,$preferences=array()) {
		$sql = sprintf(self::$GET_USER_SOCIAL_AND_PREFERENCES,$user->id);
		if(!empty($preferences)):
			array_walk($preferences,'__quoteValue');
			$sql.=sprintf(" AND type IN (%s) ",implode(',',$preferences));
		endif;
		
		//echo $sql;
		
        return $this->getDefaultAdapter()->fetch($sql);
    }

    /**
     *
     */
    public function usernameExists($username) {
        return $this->fetchWhere(array("username='".$username."'"));
    }
}
?>