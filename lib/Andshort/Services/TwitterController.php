<?php

require_once('Abstract_Service.php');

Depends('Request::Twitter::OAuth');
Depends('Request::Twitter::TwitterOAuth');

Depends('Models::User');
Depends('Models::Preference');
Depends('Models::PreferenceList');



class TwitterController extends Abstract_Service {

	protected $__name = 'twitter';

    public function defaultAction() {}

    /**
     * default handler for Oauth action with twitter
     */
    public function authAction() {

        $this->fuckOffNotLogged();

        Depends('View::Template');

        $P = new Preference($this->session->currentUser);
		$config = new ApplicationProperties();

        $this->view = new View_Template('public/twitter/auth');

        //Now we create a TwitterOAuth object.
        //The class constructor chooses HMAC-SHA1 as the signature method, and builds a OAuthConsumer object with the app consumer key/secret.
        $to = new TwitterOAuth($config['TWITTER_CONSUMER_KEY'], $config['TWITTER_CONSUMER_SECRET']);

        //With that object we use curl to request a token from twitter.
        //The API URL we hit is https://twitter.com/oauth/request_token.
        //getRequestToken() pulls the tokens from twitter, parses it into an array, and creates a new OAuthConsumer object.
        $tok = $to->getRequestToken();

        //Save the tokens for when the user returns from Twitter.
        $P->setPreference('tw_req_pub_tok', $tok['oauth_token']);
        $P->setPreference('tw_req_sec_tok', $tok['oauth_token_secret']);
        $P->setPreference('tw_req_state', 'start');

        //Set up the authorization URL.
        //This is the URL the user will visit to tell twitter the application can access their data. https://twitter.com/oauth/authorize is used.
        $request_link = $to->getAuthorizeURL($tok);
        $this->view->assign('oAuthUri',$request_link);


        $this->result = $this->view;
    }

    private function deniedAction() {
        $this->view = new View_Template('public/twitter/denied');
        $this->result = $this->view;
    }

    /**
     *
     */
    public function oauthReturnAction() {

        $this->fuckOffNotLogged();
		
		$config = new ApplicationProperties();

        $this->view = new View_Template('public/twitter/oauthReturn');

        if($this->request->hasNoEmpty('denied')):
            $this->deniedAction();die;
        endif;

        $Preferences = new Preference($this->session->currentUser);
        $P = $Preferences->fetchAll();

		/* If the access tokens are already set skip to the API call */
        if (	!$P->has('tw_acc_pub_tok') && !$P->has('tw_acc_sec_tok')	) {
		  /* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
            $to = new TwitterOAuth($config['TWITTER_CONSUMER_KEY'], $config['TWITTER_CONSUMER_SECRET'],$P->has('tw_req_pub_tok'),$P->has('tw_req_sec_tok'));
		  /* Request access tokens from twitter */
            $tok = $to->getAccessToken();
		  /* Save the access tokens. Normally these would be saved in a database for future use. */		  
            $Preferences->setPreference('tw_acc_pub_tok', @$tok['oauth_token']);
            $Preferences->setPreference('tw_acc_sec_tok', @$tok['oauth_token_secret']);
            $Preferences->setPreference('tw_req_state', 'completed');
        }

		/* Random copy */
        $content = 'your account should now be registered with twitter. Check here:<br />';
        $content .= '<a href="https://twitter.com/account/connections">https://twitter.com/account/connections</a>';

        $this->view->assign('copy',$content);

		/* Create TwitterOAuth with app key/secret and user access key/secret */
        $to = new TwitterOAuth($config['TWITTER_CONSUMER_KEY']
            , $config['TWITTER_CONSUMER_SECRET']
            , $P->has('tw_acc_pub_tok')
            , $P->has('tw_acc_sec_tok'));

		/* Run request on twitter API as user. */

        //$content = $to->OAuthRequest('https://twitter.com/account/verify_credentials.xml', array(), 'GET');
       // $content = $to->OAuthRequest('https://twitter.com/statuses/update.xml', array('status' => 'Test Python OAuth update'.date("Ymdhis").'.#python'), 'POST');
        //$content = $to->OAuthRequest('https://twitter.com/statuses/replies.xml', array(), 'GET');
        //print_r($content);

		$this->result = $this->view;
		
		die($this->result);
    }

    public function updateAction() {

        $this->renderView=false;
        $this->resultFormat = 'json_encode';
        $this->result = array();

        if(	isset($this->params->tweetMessage) && !empty($this->params->tweetMessage) && isset($this->session->currentUser) && ($this->session->currentUser instanceof UserRow)	):

        //Twitter
            Depends("Request::SimpleTwitter");
            $SimpleTwitter = new SimpleTwitter($this->session->currentUser);
            $strMessage = addslashes($this->params->tweetMessage);

            if($SimpleTwitter->canTwit()):
                $message = $SimpleTwitter->tweet(addslashes(urldecode($strMessage)));
                $this->result = $message;
            else:
        //echo "!canTwit";
        endif;

    endif;
    }

    /**
     * @private
     */
    private function _userLogged() {
        global $_SESSION;
        return array_key_exists('currentUser', $_SESSION) && !empty($_SESSION['currentUser']);
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
}
?>