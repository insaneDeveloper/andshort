<?php
require_once('Abstract_Service.php');
class IndexController extends Abstract_Service {

    private $dbConnection;
    private $session;

    public $view_class = 'Smarty';

    public $renderView = true;
    public $contentType = 'text/html';

    protected $__name = 'index';

    /**
     * default contructor.
     **/
    public function __construct() {
        $this->session  = Control_SessionManager::getInstance();
        $this->request = new Control_Request;
        $this->params  = $this->request->getParams();

        $this->setupConfig();
    }

    //-------------------------------------------------------------------------------------||
    // Internal Helper Methods ------------------------------------------------------------||
    //-------------------------------------------------------------------------------------||

    /**
     * @private
     */
    private function setupConfig() {
        global $_SESSION;
        Depends('Language_Xml_Parser');

        $this->language = Language_Xml_Parser::parseLanguageXmlFile('private/languages/default.xml');

        $_SESSION['short_config'] = array();


        if (!isset($_SESSION['sh_shortys01']))
            $_SESSION['sh_shortys01'] = array();

        $_SESSION['short_config']['language'] = 'default';

        $_SESSION['short_config']['myShortUrls'] = implode("\n", array_map(array($this,'addHostPrefix'), array_keys($_SESSION['sh_shortys01'])));

        //currentUser
        if (array_key_exists('currentUser', $_SESSION)):
            foreach ($_SESSION['currentUser'] as $k => $v):
                $_SESSION['short_config'][$k] = $v;
            endforeach;
    endif;

    }

    /**
     *
     */
    protected function getUrlByShort($short) {
        Depends("Models::Url");
        $Url = new Url();
        return $Url->findByShorted($short);
    }

    /**
     * @private
     */
    private function redirectShortUri($short, $abs = false) {
        if (!$abs):
            $result = $this->getUrlByShort($short);
            if (is_array($result) && !empty($result)):
                header("Location: " . $result[0]['originalUrl']);
            else:
                header("Location: /visited/" . $short, false, 201);
        endif;
        else:
        //Simple redirect
            header("Location: " . $short, false, 302);
    endif;
    //print_r($result);
    }

    /**
     *
     */
    protected function _getUrisByUser($userid = null) {
        if (!$userid)
            $userid = $this->session->currentUser->id;
        Depends("Models::Url");
        $u = new Url();
        return $u->getUrisByUserId($userid);
    }

    /**
     * @private
     */
    private function _userLogged() {
        global $_SESSION;
        return array_key_exists('currentUser', $_SESSION) && !empty($_SESSION['currentUser']);
    }

    /**
     *
     */
    protected function sendEmail(array $options) {
        $headers  = "Content-Type:text/html \r\n";
        $template = new View_Template($options['template']);
        $template->AssignArray($options['contentArray']);
        return mail($options['to'], $options['subject'], (string)$template, $headers);
    }

    //-------------------------------------------------------------------------------------||
    // RPC Methods             ------------------------------------------------------------||
    //-------------------------------------------------------------------------------------||

    /**
     *
     */
    public function mainAction() {
        $this->defaultAction();
    }
    public function indexAction() {
        $this->defaultAction();
    }

    public function addonsAction() {		}
    public function apiAction() {	}
    public function contactAction() {	}
    public function featureAction() {	}

    public function passAction() {

    //print_r($this->session);
    //print_r($this->params);

        if (isset($this->params->recoverypass) && $this->request->getMethod() == "POST"):

            Depends('Models::User');
            Depends('Models::Preference');
            Depends('Models::Row::UserRow');

            $this->renderView=false;
            $this->resultFormat='json_encode';

            $User = new User();
            $UserRow = $User->fetchNewRow();
            $UserRow['email'] = $this->params->recoverypass;
            //$passwordRes = $User->getPassword($UserRow)->asArray();
            $finded = $User->findByEmail($UserRow);
			
			if(!$finded instanceof UserRow) return array();

            $app_properties = new ApplicationProperties();

            $P = new Preference($finded);
            $password_recovery_requested_time = time();
            $P->setPreference('password_recovery_requested', 'true');
            $P->setPreference('password_recovery_requested_time', $password_recovery_requested_time);

            $file  = 'private/templates/' . (isset($this->session->language) ? $this->session->language : 'default');
            $file .= '/lost_password';

            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/index/password_recovery/?';
            $url.= http_build_query(array(
                'confirm'=>$password_recovery_requested_time
                ,'act'=>$finded->actkey
                ,'u'=>base64_encode($finded->email)
            ));


            $emailResulted = $this->sendEmail(array(
                'from' => $app_properties['EMAIL_FROM'],
                'to' => $finded->email,
                'subject' => 'Password',
                'template' => $file,
                'contentArray' => array(
                'where_site' => $_SERVER['HTTP_HOST'],
                'from_mail' => $app_properties['EMAIL_FROM'],
                'url' => $url,
                'user' => $finded->username,
                'password' => $finded->password,
                'secretKey' => $finded->secretKey
                )
            ));

            $this->result = array('sended'=>$emailResulted,'finded'=>true,'requested'=>time());
            return;

        endif;

        $this->view->assign('mainScript','/public/js/pass/main.js');

    }


    public function password_recoveryAction() {

        $this->renderView=true;
        $this->view = new View_Template('view/generic');

        Depends('Models::User');
        Depends('Models::Preference');
        Depends('Models::Row::UserRow');

        $User = new User();
        $UserRow = new UserRow;
        $UserRow['email'] = base64_decode(urldecode($this->params->u));

        $finded = $User->findByEmail($UserRow);

        if($finded instanceof UserRow):
            $user = $finded->asArray();
            $messageTitle = "Completado";
            $message = "Se te ha enviado una contrase&ntilde;a provisional a tu correo, en cuanto accedas te recomendamos cambiarla enseguida.";

            $P = new Preference($finded);
            $preferences = $P->fetchAll();
            $hasBeenSent = $preferences->has('password_recovery_sent');


            if(!$hasBeenSent):

                $file  = 'private/templates/' . (isset($this->session->language) ? $this->session->language : 'default');
                $file .= '/temporal_password';
                $password =  substr(md5(strtolower(str_replace('=','',base64_encode(time())))),0,8);
                $app_properties = new ApplicationProperties();


                $finded->password = $password;
                $User->update($finded);

                $emailResulted = $this->sendEmail(array(
                    'from' => $app_properties['EMAIL_FROM'],
                    'to' => $user['email'],
                    'subject' => 'Password',
                    'template' => $file,
                    'contentArray' => array(
                    'where_site' => $_SERVER['HTTP_HOST'],
                    'from_mail' => $app_properties['EMAIL_FROM'],
                    'user' => $user['username'],
                    'password' => $password
                    )
                ));


                $P->setPreference('password_recovery_sent','true');

                $P->removePreference('password_recovery_requested');
                $P->removePreference('password_recovery_requested_time');

        endif;
        endif;

        $this->view->assignArray(array(
            'messageTitle'=>$messageTitle
            ,'message'=>$message
        ));
    }

    public function errorAction() {

    }
    public function registroAction() {
        $this->view->assign('mainScript',JS_DIR.'/register/main.js');
    }


    /**
     *
     */
    public function profileeditAction() {
        $this->fuckOffNotLogged();

        $P = new Preference($this->session->currentUser);
        $preferences = $P->fetchAll();

        $this->view->assign('social_twitter',$preferences->has('twitter'));
        $this->view->assign('social_facebook',$preferences->has('facebook'));
        $this->view->assign('social_delicious',$preferences->has('delicious'));
        $this->view->assign('social_flickr',$preferences->has('flickr'));
        $this->view->assign('social_lastfm',$preferences->has('lastfm'));
        $this->view->assign('social_linkedin',$preferences->has('linkedin'));
        $this->view->assign('social_tumblr',$preferences->has('tumblr'));
        $this->view->assign('social_youtube',$preferences->has('youtube'));
    }

    public function preferenceSaverAction() {
        $this->fuckOffNotLogged();
        $this->renderView=false;

        if ($this->request->getMethod() == "POST"):

            $p      = $this->request->getPost();

            if(!isset($p->use_iframe))
                $this->removeUserPreference($this->session->currentUser,'use_iframe');

            foreach ($p as $k => $v):
                $this->setUserPreference($this->session->currentUser,$k,$v);
            endforeach;

        endif;

        $this->redirectShortUri('/index/profileconfig', true);
    }
    /**
     *
     */
    public function profilesaverAction() {
        $this->fuckOffNotLogged();

        $this->renderView=false;

        if ($this->request->getMethod() == "POST"):

            $p      = $this->request->getPost();
            //print_r($p);die;
            $uid    = $this->session->currentUser->id;

            $fields = array();
            foreach ($p as $k => $v):
            //socialmedia urls
                if (substr($k, 0, 7) == 'social_'):
                    $this->setUserPreference($this->session->currentUser, substr($k, 7), $v);
                else:
                    if (!empty($v) && strlen($k) > 2):
                        $fields[$k]                  = $v;
                        $_SESSION['currentUser']->$k = $v;
                        $_SESSION[$k]                = $v;
                endif;
            endif;

            endforeach;

            if (!empty($fields))
                $this->updateUserData($uid, $fields);
        endif;

        //print_r($fields);
        //print_r($_SESSION);
        $this->redirectShortUri('/index/profileedit', true);

    }

    /**
     * @private
     */
    private function updateUserData($id, array $fields) {

        Depends("Models::Url");

        $UrlDao = new Url();

        $VALUES = '';

        foreach ($fields as $k => $v):
            $VALUES .= "$k='$v',";
        endforeach;

        $VALUES = ereg_replace(',+$', '', $VALUES);

        $UP = "UPDATE users SET $VALUES WHERE id='$id' LIMIT 1";

        //echo $UP."<br/>";

        try {
            $UrlDao->getDefaultAdapter()->execute($UP);
        }
        catch (Exception $x) {
            /**/
        }
    }

    /**
     * @private
     */
    private function setUserPreference(UserRow  $user, $type, $value) {
        Depends('Models::Preference');
        Depends('Models::User');

        $P = new Preference($user);
        $P->setPreference($type, $value);

        return $user;
    }

    private function removeUserPreference(UserRow  $user, $type) {
        Depends('Models::Preference');
        Depends('Models::User');

        $P = new Preference($user);
        $P->removePreference($type);

        return $user;
    }

    public function profileconfigAction() {

        $this->fuckOffNotLogged();

        $P = new Preference($this->session->currentUser);
        $preferences = $P->fetchAll();

        //---------------------------------------------<Analytics

        $input_use_analytics = new View_Html_Form_Element_Checkbox("use_analytics");
        $input_use_analytics->setAttribute("id","andshort_config_0")
            ->setAttribute("class","large_check")
            ->checked($preferences->has('analytics_id'));

        //<input type="text" value="{pref.adsense_id}" id="adsense_id" class="txt round3" name="adsense_id"/>
        $input_adsense_id = new View_Html_Form_Element_Input("adsense_id");
        $input_adsense_id->setAttribute("id","adsense_id")
            ->setAttribute("class","txt round3")
            ->setAttribute("value",$preferences->has('adsense_id'));

        $input_use_iframe = new View_Html_Form_Element_Checkbox("use_iframe");
        $input_use_iframe->setAttribute("id","use_iframe")
            ->setAttribute("class","txt round3");


        #---------------------------Trash element------------------------------------------
        $time_trash_select = new View_Html_Form_Element_Select("time_trash");
        $time_trash_select->setAttribute("id","time_trash");
        $time_trash_select->setOptions(array(
            '10'=>10,
            '20'=>20,
            '30'=>30
        ));

        if($preferences->has('time_trash'))
            $time_trash_select->setSelected($preferences->has('time_trash'));
        #---------------------------/Trash element------------------------------------------

        if($preferences->has('use_iframe'))
            $input_use_iframe->setAttribute("checked",$preferences->has('use_iframe'));

        $this->view->assign('time_trash_select',(string)$time_trash_select);
        $this->view->assign('use_analytics',(string)$input_use_analytics);
        $this->view->assign('adsense_input',(string)$input_adsense_id);
        $this->view->assign('use_iframe',(string)$input_use_iframe);

        $input = new View_Html_Form_Element_Input("analytics_id");

        $input->setType("input")
            ->setAttribute("id","analytics_id")
            ->setAttribute("class","txt round3")
            ->setAttribute("value",$preferences->has('analytics_id'));



        $this->view->assign('analytics_input',(string)$input);
        //---------------------------------------------Analytics>

        $this->view->assign('pref.analytics_id',$preferences->has('analytics_id'));
        $this->view->assign('pref.adsense_id',$preferences->has('adsense_id'));
        $this->view->assign('pref.use_recycler',$preferences->has('use_recycler'));

    }

    /**
     *
     */
    public function importAction() {
        $this->fuckOffNotLogged();
    }

    /**
     *
     */
    public function rankingAction() {
        Depends('View::TagCloudMaker');
        Depends('Models::Tag');
        Depends('Models::Url');

        $Tag      = new Tag;

        $ranks = $Tag->getDefaultAdapter()->fetch(sprintf(Sql_Logic::TOP10URL));
        $clouds   = $Tag->fetchRandomTags(30);
        $clouder  = new Visual_TagCloudMaker($clouds, array(
            'st-tags t1',
            'st-tags t2',
            'st-tags t3'
        ));

        $tagcloud = $clouder->tagcloud('getName');

        $this->view->assign('tagcloud', $tagcloud);
        $this->view->assign('ranking', $ranks);


    }

    /*
	public function shorturlAction() {
        global $_SESSION;

        $short = "";

        //$this->result=array('1','2','3');

        if (isset($this->params->shortUrl)):
            $exists = $this->existsShortUrl($this->params->shortUrl);
            //print_r($exists);
            if (is_array($exists) && !empty($exists)):
                $this->result = array(
                    'result' => 'ESHORT_TAKEN',
                    'error' => 1
                );
                return;
            else:
                $short = $this->params->shortUrl;
        endif;
        endif;

        if (empty($short))
            $short = $this->randomShort();

        if (isset($this->params->url)):
            try {
                $this->dbConnection->execute(sprintf(Sql_Logic::REGISTER_URL, $short, $this->params->url, '', $this->params->password));
            }
            catch (Exception $error) {
                switch ($error->getMessage()):
                    case '1062':
                    #duplicated attempt
                        $this->result = $this->dbConnection->fetch(sprintf(Sql_Logic::FIND_URL, $this->params->url));
                        break;
                endswitch;

                return;
            }

            $this->result = $this->dbConnection->fetch(sprintf(Sql_Logic::FIND_URL, $this->params->url));


            if (!isset($_SESSION['sh_shortys01']))
                $_SESSION['sh_shortys01'] = array();
            $_SESSION['sh_shortys01'][$this->result[0]['shortUrl']] = array(
                'shortUrl' => main_url . $this->result[0]['shortUrl'],
                'originalUrl' => $this->params->url,
                'shortUrlTags' => ''
            );
        endif;

        return "shorturlAction";
    }
	*/

    public function errAction() {
        $this->renderView=false;
        $this->resultFormat = 'json_encode';
        $this->result="";

		//print_r($this->language);
		
        $post = $this->request->getParams();

        if (isset($post->e)) {
            $prop = $post->e;
			$meaning = $this->language->$prop;
			if(!empty($meaning)){
				$this->result = array(
					'translate' => $meaning
				);
			}
        }


    }

    /**
     *
     */
    public function existsAction() {
        $this->renderView=false;
        $this->result="";

        $ret = (object) array(
            'exists' => false
        );

        $post                = $this->request->getPost();
        $exists              = $this->existsShortUrl($exists);
        $ret->exists         = is_array($exists) && !empty($exists);

        $this->resultFormat = 'json_encode';
        $this->result    = $ret;
    }

    /**
     * @private
     */
    private function existsShortUrl($short) {
        return $this->dbConnection->fetch(sprintf(Sql_Logic::FIND_URL_BY_SHORT, $short));
    }


    public function formviewAction() {
        $this->renderView=false;
        $this->result="";

        Depends('Models::User');
        Depends('Models::Row::UserRow');
        Depends('Models::Preference');


        $UserRow = $this->session->currentUser;
        $P = new Preference($UserRow);
        $preferencesList = $P->fetchAll()->asArray();

        $select = new View_Html_Form_Element_Select("combobox");
        $select->setFromArray($preferencesList)
            ->bind('change','alert(this.options[this.selectedIndex].value);');

        $input = new View_Html_Form_Element_Input("facebook");
        $input->setType("button")
            ->setAttribute("id","facebook")
            ->setAttribute("value","insane");

        $checkbox = new View_Html_Form_Element_Radio("checkbox");

        $this->result = (string)$checkbox;
    }

    public function logoutAction() {
        $this->renderView=false;
        $this->result="";
        @session_destroy();
		header('Location: /',true,301);
    }
    public function ArrayAccessAction() {

        Depends('Models::User');
        Depends('Models::Row::UserRow');
        Depends('Models::Preference');

        $this->renderView=false;
        $this->result="";

		/*
		$UserRow = new UserRow();
		$UserRow->setFromArray(array("username"=>"insane","email"=>"insane@instropy.com"));
		$UserRow["username"]="Oscar";		
		print_r($UserRow);
		*/

        $UserRow = $this->session->currentUser;
        $P = new Preference($UserRow);
        $preferences = $P->fetchAll();



        foreach($UserRow as $k => $v):
            echo " $k , $v <br />\n";
        endforeach;

        foreach($preferences as $k => $v):
            echo " $k , $v <br />\n";
        endforeach;

    }

    private static function addHostPrefix($a) {
        return main_url . $a;
    }

    /**
     * @method view defaultAction serves  At /index/index or /index/
     * @access public
     */
    public function defaultAction() {

        error_reporting(E_ALL);
        $this->renderView = true;

        Depends("Models::Categorie");
        Depends("Request::SimpleTwitter");
        Depends('Models::Preference');

        $logged = $this->_userLogged();
        $content = @$this->session->sh_shortys01;

        if (empty($content))
            $content = array();

        $this->view->assign('shortys', $content);


        if ($logged):

            $currentUser = $this->session->currentUser;
			$SimpleTwitter = new SimpleTwitter($currentUser);
            $P = new Preference($currentUser);
            $preferences = $P->fetchAll();

            //######################Categories######################
            $Categorie = new Categorie();
            $cats = $Categorie->getUserCategories($currentUser);
            $this->view->assign('categories', $cats);

            //######################Social######################
            $share_select_cbx = new View_Html_Form_UserSocialCombo('share_accounts',$currentUser);

            $can_share = !empty($share_select_cbx->options);

            $this->view->assign('twittAllow',$can_share);
            $this->view->assign('twittAllowBtnDisplay',!$can_share ? "display" : "none");
            $this->view->assign('twittAllowAuthUrl',!$can_share ? $SimpleTwitter->getRequestAccessLink() : "");
            $this->view->assign('messageTwitter',sprintf(
                ($can_share ?
                Twitter_Logged_CanTwitMessage :
                Twitter_Logged_CantTwitMessage),
                $this->session->currentUser->username,
                ''
            ));

            //button share
            $in = new View_Html_Form_Element_Input('senderBtn');
            $in->setAttribute('type','image')
                ->setAttribute('class','button')
                ->setAttribute('src','/public/pix/iface/btnSend.png')
                ->bind('click','and_share($("#share_accounts option:selected").val(),$(this).parent().parent().parent().find("#twittArea").val(),shortener.shareResult);');
            $this->view->assign('senderBtn',(string)$in);

            $this->view->assign('social_accounts',$share_select_cbx);

        endif; //end logged

        $this->view->assign('mainScript','/public/js/'.$this->name().'/main.js');

    }

    /**
     *
     */
    public function topAction() {
        $this->result = $this->dbConnection->fetch(sprintf(Sql_Logic::TOP10URL));
    }

    /**
     *
     */
    public function frameAction() {
        if(isset($this->params->shortId)):

            Depends('View::TagCloudMaker');
            Depends('Models::Tag');
            Depends('Models::Url');

            $Tag = new Tag();

            $uid = (int)base64_decode($this->params->shortId);
            $clouds   = $Tag->getRelatedTags($uid);

            if(sizeof($clouds)):
                $clouder  = new Visual_TagCloudMaker($clouds, array('simple_tag'));
                $tag_cloud = $clouder->tagcloud();
                $this->view->assign('loopCloud',$tag_cloud);
        endif;

        endif;


        $this->view->assign('params',$this->params);
    }
    /**
     * @private
     */
    private function fuckOffNotLogged() {
        if (!$this->_userLogged()):
            header("Location: /", 404);
            exit;
    endif;
    }

    /**
     *
     */
    public function profileAction() {
        $this->fuckOffNotLogged();

        $uris     = $this->_getUrisByUser();

        $totalHits = 0;
        $publics   = 0;
        $privates  = 0;
        $recicle   = 0;


        while ($a = current($uris)):
            $totalHits += $a->visitedTimes;
            if ($a->visibility != 'public')
                $privates++;
            next($uris);
        endwhile;

        $this->view->assign('myuris', $uris);

        $this->view->assign('totalUrls', count($uris));
        $this->view->assign('totalHits', $totalHits);
        $this->view->assign('totalPrivates', $privates);
        $this->view->assign('totalRecicle', $recicle);

    }

    /**
     *
     */


    /**
     *
     */
    public function profileadminAction() {
        $this->fuckOffNotLogged();

        Depends("Models::Categorie");

        $Categorie = new Categorie();

        $cats         = $Categorie->getDefaultAdapter()->fetch(sprintf(Sql_Logic::FETCH_CATEGORIES_BY_UID, $this->session->currentUser->id));
        //$cats = $Categorie->getDefaultAdapter()->fetch(sprintf(Sql_Logic::FETCH_CATEGORIES_BY_UID,$this->session->currentUser->id));

        $howManyLinks = 0;

        while ($c = current($cats)):
        //$howManyLinks += $c->countUris;
            $howManyLinks += $c->countUris;
            next($cats);
        endwhile;
        reset($cats);

        //print_r($cats);

        $this->view->assign('categories', $cats);
        $this->view->assign('countUris', $howManyLinks);
        $this->view->assign('countCategories', count($cats));

        $this->view->assign('mainScript','/public/js/cat_uris/main.js');

    }

    /**
     *
     */
    public function dropCategorieAction() {
        $this->fuckOffNotLogged();
		
		$this->renderView = false;
		
		$c = new Categorie();

        $cid = isset($this->params->uid) ? $this->params->uid : -1;
        $uid = $_SESSION['currentUser']->userid;

        $sql = Sql_Logic::DROP_CATEGORIE;
        $sql = sprintf($sql, $cid, $uid);

        $c->getDefaultAdapter()->execute($sql);

        if (isset($this->params->__redirect) && !empty($this->params->__redirect)):
            $this->redirectShortUri($this->params->__redirect, true);
            exit;
    endif;
    }

    /**
     *
     */
    public function dropUriAction() {
        $this->fuckOffNotLogged();
		
		$this->renderView=false;

        $cid = $this->params->uid;
        $uid = $_SESSION['currentUser']->userid;
		
        $sql = sprintf(Sql_Logic::DROP_URI, $cid, $uid);

        $Url = new Url();
		$Url->getDefaultAdapter()->execute($sql);

        if (isset($this->params->__redirect) && !empty($this->params->__redirect)):
            $this->redirectShortUri($this->params->__redirect, true);
            exit;
		endif;
    }
    /**
     *
     */
    public function toggleCategorieAction() {
        $this->fuckOffNotLogged();

        $this->renderView=false;
        $this->result = array();
        $this->resultFormat = 'json_encode';

        $cid = $this->params->uid;
        $uid = $this->session->currentUser->id;

        $sql = Sql_Logic::TOGGLE_VISIBILITY;
        $sql = sprintf($sql, $cid);
		
		$c = new Categorie();

		$c->getDefaultAdapter()->execute($sql);

        $fetch = $c->getDefaultAdapter()->fetch(sprintf(Sql_Logic::GET_CATEGORIE_BY_UID, $cid));

        if (!count($fetch)) {
            $this->result = array(
                "error" => ESHORT_NOT_SUCH_CAT_ID
            );
        } else {
            $this->result = $fetch[0];
        }

        if (isset($this->params->__redirect) && !empty($this->params->__redirect)):
            $this->redirectShortUri($this->params->__redirect, true);
            exit;
    endif;

    }

    /**
     *
     */
    public function toggleUriAction() {
        $this->fuckOffNotLogged();
        $this->renderView=false;

        Depends("Models::Categorie");
        $Categorie = new Categorie();

        $this->result = array();

        $urid = $this->params->uid;
        $uid  = $_SESSION['currentUser']->userid;

        $sql = Sql_Logic::TOGGLE_URI_VISIBILITY;
        $sql = sprintf($sql, $urid);

        $Categorie->getDefaultAdapter()->execute($sql);

        $this->resultFormat = 'json_encode';

        $getUri = sprintf(Sql_Logic::GET_URI_BY_UID, $urid);

        $fetch = $Categorie->getDefaultAdapter()->fetch($getUri);

        if (!count($fetch)) {
            $this->result = array(
                "error" => ESHORT_NOT_SUCH_URI_ID
            );
        } else {
            $this->result = $fetch[0];
        }

        if (isset($this->params->__redirect) && !empty($this->params->__redirect)):
            $this->redirectShortUri($this->params->__redirect, true);
            exit;
    endif;

    }

    /**
     *
     */
    public function addCategorieAction() {

        $this->fuckOffNotLogged();
        $this->renderView=false;
        $this->resultFormat = 'json_encode';

        if (
        $this->request->getMethod() == "POST" &&
            $this->_userLogged()
            && array_key_exists('categorieName',$this->params)

        ):
            try {
			
			$c = new Categorie();
			
            //print_r($this->params);die;

                $uid       = $this->session->currentUser->id;
                $name      = $this->params->categorieName;
                $isPrivate = array_key_exists('newcat_status',$this->params) && $this->params->newcat_status == 'private';

                if (!empty($uid) && !empty($name)):
                    $sql = $isPrivate ? Sql_Logic::NEW_USER_CATEGORY : Sql_Logic::NEW_USER_PRIVATE_CATEGORY;
                    $sqlk = sprintf($sql, $name, $uid, 'NOW()');

                    //echo $sqlk;die;

                    $cats = $c->getDefaultAdapter()->execute($sqlk);

                    $this->result = array(
                        'categorie' => $name
                    );
                else:
                    $this->result['error'] = ESHORT_MISSING_PARAMS;
            endif;

            }
            catch (Exception $_ex) {
                return;
            }

            if (isset($this->params->__redirect) && !empty($this->params->__redirect)):
                $this->redirectShortUri($this->params->__redirect, true);
                exit;
        endif;
        else:
            $this->result['error'] = ESHORT_NOT_LOGGED;
    endif;
    }
	/*
	*
	*/
    public function loginAction() {
        Depends('Models::User');
        Depends('Models::Row::UserRow');

        $this->renderView = false;
        $this->resultFormat='json_encode';

        $User = new User;
        $UserRow = $User->fetchNewRow();

        $post  = $this->request->getPost();
        //$post  = $this->request->getParams();

        $UserRow['email'] = isset($post->user) ? addslashes($post->user) : NULL;
        $UserRow['password']  = isset($post->pass) ? addslashes($post->pass): NULL;

        if(!$UserRow['email'] || !$UserRow['password']) return;

        $result = $User->checkLogin($UserRow);
        //print_r($result);

        if($result instanceof UserRow):

            $User->updateLastActivity($result);

            $P = new Preference($result);

            if(isset($_SERVER['UNIQUE_ID'])) {
                $P->setPreference('authenticity_token',md5($_SERVER['UNIQUE_ID']));
            }else {
                $P->setPreference('authenticity_token',md5(time()));
            }

            session_register('currentUser');
            $_SESSION['currentUser'] = $result;

            $this->result = $result->asArray();

        else:

    endif;

    }

    /**
     *
     */
    public function activateAction() {
       
	    $this->renderView = false;
		
	    $params		= $this->params;
        $actkey		= @$params->actkey;
        $template = new View_Template('view/activated.html');
		$user = new User();

        $the_user_result = $user->getDefaultAdapter()->fetch(sprintf(Sql_Logic::FETCH_USER_BY_ACTKEY, $actkey));

        if (sizeof($the_user_result)):
            $the_user = $the_user_result[0];
            //print_r($the_user);die;
            $file = 'private/templates/' . (array_key_exists('language', $_SESSION) ? $_SESSION['language'] : 'default');
            $file .= '/welcome_activated.html';

            $app_properties = new ApplicationProperties();

            if (!empty($the_user) && !$the_user->confirmedMail):

                $result = $user->getDefaultAdapter()->execute(sprintf(Sql_Logic::ACTIVATE_USER, $actkey));

                $this->sendEmail(array(
                    'from' => $app_properties['EMAIL_FROM'],
                    'to' => $the_user->email,
                    'subject' => 'Bienvenida',
                    'template' => $file,
                    'contentArray' => array(
                    'where_site' => $_SERVER['HTTP_HOST'],
                    'from_mail' => $app_properties['EMAIL_FROM'],
                    'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/index/activate/?actkey=' . $actkey,
                    'user' => $the_user->username,
                    'password' => $the_user->password,
                    'secretKey' => $the_user->secretKey
                    )
                ));

                $template->AssignArray(array(
                    'message' => 'Gracias por registrarte , en breve recibiras un correo con tu contrase&ntilde;a.'
                ));


            else:
                $template->AssignArray(array(
                    'message' => 'Cuenta ya activada anteriormente.'
                ));
        endif;
        endif; // key found

        echo $template;
    }

    /**
     *
     */
    public function visitedAction() {
    //print_r($_SERVER);die;
    //die($this->params->shortUrl);

        if (isset($this->params->shortUrl)):
            $template = new View_Template('public/visited');
            $visited  = $this->getUrlByShort($this->params->shortUrl);

            if (!empty($visited)):
                $this->view->AssignArray(array(
                    'headerFrame' => $visited[0]['originalUrl'],
                    'uaTracker' => $visited[0]['uaTracker'],
                    'visitedTimes' => $visited[0]['visitedTimes']
                ));

                $this->result    = $template;
                $sql                 = sprintf(Sql_Logic::UPDATE_VISITED_TIMES, $visited[0]['shortUrl']);
                $this->dbConnection->execute($sql);

            else:
                header("Location: /");
        endif;


        else:
            header("Location: /");
    endif;
    }


    public function registerUserAction() {
        $this->renderView=false;
        $this->resultFormat = 'json_encode';

        if (
        isset($this->params->email)
            && isset($this->params->username)

            && !empty($this->params->email)
            && !empty($this->params->username)
        ):

            $app_properties = new ApplicationProperties();

            try {
                $secretKey       = $app_properties['PREFIX_SKEY'][rand(0, count($app_properties['PREFIX_SKEY']) - 1)] . md5($this->params->email);
                $createdPassword = substr(uniqid(time()), 0, 8);

                $actkey = md5('DTSE' . $this->params->email);

                $sql = sprintf(Sql_Logic::NEW_USER, $this->params->username, $this->params->email, $createdPassword, $secretKey, $actkey);

                $file = 'private/templates/' . (array_key_exists('language', $_SESSION) ? $_SESSION['language'] : 'default');
                $file .= '/welcome_mailing.html';
                //echo $file;

				$User = new User;

                if ($User->getDefaultAdapter()->execute($sql)):
                //send email
                    $this->result = $this->sendEmail(array(
                        'from' => $app_properties['EMAIL_FROM'],
                        'to' => $this->params->email,
                        'subject' => 'Activación de cuenta',
                        'template' => $file,
                        'contentArray' => array(
                        'where_site' => $_SERVER['HTTP_HOST'],
                        'from_mail' => $app_properties['EMAIL_FROM'],
                        'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/index/activate/?actkey=' . $actkey
                        )
                    ));
            endif;

            }
            catch (Exception $error) {
                switch ($error->getMessage()):
                    case '1062':
                    #duplicated attempt
                        $this->result = array(
                            'result' => 'ESHORT_USERTAKEN',
                            'error' => 1
                        );
                        break;
                    default:
                        $this->result = array(
                            'result' => 'ESHORT_USER_EMAIL_TAKEN',
                            'error' => $error->getMessage()
                        );
                        break;
                endswitch;
            }
        else:
            die("Missing");
    endif;

    //print_r($this);die;
    }
}
#Alias
final class HomeController extends IndexController {}
?>