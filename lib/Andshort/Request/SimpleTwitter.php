<?php
Depends('Request::Twitter::OAuth');
Depends('Request::Twitter::TwitterOAuth');

Depends('Models::User');
Depends('Models::Row::UserRow');
Depends('Models::Preference');
Depends('Models::PreferenceList');



class SimpleTwitter {

    private $preferenceModel;
    private $preferencesList;
    private $userData;
	
	private $app_properties;
	
    private $messenger;
    private $requestLink;


    public function __construct(UserRow $user) {

        $this->userData = clone $user;

        $this->preferenceModel =  new Preference($this->userData);
        $this->preferencesList = $this->preferenceModel->fetchAll();

		$this->app_properties = new ApplicationProperties();

        $this->initializeTwitterStuffs();
    }

    public function initializeTwitterStuffs() {

        $tok=array();
        $tok['oauth_token'] = $this->preferencesList->has('tw_req_pub_tok');
        $tok['oauth_token_secret'] = $this->preferencesList->has('tw_req_sec_tok');

        $completed = $this->preferencesList->has('tw_req_state')=="completed";

        if (!$completed) {

        //Now we create a TwitterOAuth object.
        //The class constructor chooses HMAC-SHA1 as the signature method, and builds a OAuthConsumer object with the app consumer key/secret.
            $this->messenger = new TwitterOAuth($this->app_properties['TWITTER_CONSUMER_KEY'], $this->app_properties['TWITTER_CONSUMER_SECRET']);

				/* Request new access tokens from twitter */
            $tok = $this->messenger->getRequestToken();

            if($tok && !empty($tok)):

					/* Save the access tokens. Normally these would be saved in a database for future use. */		  
                $this->preferenceModel->setPreference('tw_req_pub_tok', $tok['oauth_token']);
                $this->preferenceModel->setPreference('tw_req_sec_tok', $tok['oauth_token_secret']);

                $this->preferencesList = $this->preferenceModel->fetchAll();

					/* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
				$this->messenger = new TwitterOAuth($this->app_properties['TWITTER_CONSUMER_KEY']
				, $this->app_properties['TWITTER_CONSUMER_SECRET']
				,$tok['oauth_token']
				,$tok['oauth_token_secret']);

                $this->requestLink = $this->messenger->getAuthorizeURL($tok);

        endif;
        }else {
            $this->messenger = new TwitterOAuth(
                $this->app_properties['TWITTER_CONSUMER_KEY']
                , $this->app_properties['TWITTER_CONSUMER_SECRET']
                , $this->preferencesList->has('tw_acc_pub_tok')
                , $this->preferencesList->has('tw_acc_sec_tok')
            );
        }


    }

    public function canTwit() {

        $this->preferencesList = $this->preferenceModel->fetchAll();

        $tw_req_pub_tok = $this->preferencesList->has('tw_req_pub_tok');
        $tw_req_sec_tok = $this->preferencesList->has('tw_req_sec_tok');
        $tw_req_state   = $this->preferencesList->has('tw_req_state');

        $canTwit = $tw_req_pub_tok && $tw_req_sec_tok && $tw_req_state=='completed';

        //echo "canTwitf(f) = $tw_req_pub_tok::$tw_req_sec_tok::$tw_req_state ";

        return $canTwit;
    }

    public function getRequestAccessLink() {
        return $this->requestLink;
    }

    public function getTwitterAcounts($classList='PreferenceTwitterList') {
        $twitters =  $this->preferenceModel->fetchAll(
			sprintf('type="%s" and userid="%d"','twitter',$this->userData->id)
			,$classList
		);
		//print_r($twitters);
        return $twitters;
    }

    public function tweet($message) {
        try {
            $returnXml = $this->messenger->OAuthRequest('https://twitter.com/statuses/update.xml', array('status' => addslashes($message)), 'POST');

            $items=array();
            $p = xml_parser_create();
            xml_parse_into_struct($p, $returnXml, $vals);
            xml_parser_free($p);

            return (object)array("ID"=>$vals[3]['value']);

        }catch(Exception $ex ){
            echo $ex->getMessage();
        }
    }
} 
?>