<?php
Depends('Models::User');
Depends('Models::Row::UserRow');
Depends('Models::Preference');
Depends('Models::PreferenceList');



class SimpleDelicious {

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

        $this->initialize();
    }
	
	protected function initialize(){
		
	}
	
	public function getPreferenceModel(){
		return  $this->preferenceModel;
	}
	
	public function setOauthVerifier($value){
		$this->preferenceModel->setPreference('del_oauth_verifier',$value);
	}
	
	public function setOauthToken($value){
		$this->preferenceModel->setPreference('del_oauth_token',$value);
	}
	
	public function setOauthTokenSecret($value){
		$this->preferenceModel->setPreference('del_oauth_token_sec',$value);
	}
	
	public function setOauthTokenSessionHandle($value){
		$this->preferenceModel->setPreference('del_oauth_session_handle',$value);
	}
	
	public function setOauthNonce($value){
		$this->preferenceModel->setPreference('del_oauth_nonce',$value);
	}
	
	
	public function getOauthVerifier(){
		return $this->preferenceModel->getPreference('del_oauth_verifier');
	}
	
	public function getOauthToken(){
		return $this->preferenceModel->getPreference('del_oauth_token');
	}
	
	public function getOauthTokenSecret(){
		return $this->preferenceModel->getPreference('del_oauth_token_sec');
	}
	
	public function getOauthTokenSessionHandle(){
		return $this->preferenceModel->getPreference('del_oauth_session_handle');
	}
	
	public function getOauthNonce(){
		return $this->preferenceModel->getPreference('del_oauth_nonce');
	}
    
} 
?>