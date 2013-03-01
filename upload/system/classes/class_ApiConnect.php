<?php
class ApiConnect{

	private $base_url;
	private $username;
	private $password;
	protected $curl;

	public function __construct($base_url = "", $username = "", $password = ""){
		$this->username = $username;
		$this->password = $password;
		$this->base_url = $base_url;
	}

	public function setUser($username){
		$this->username = $username;
	}

	public function setPassword($password) {
		$this->password = $password;
	}

	public function open(){
		$this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($this->curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) API User");
		curl_setopt($this->curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
	}

	public function get($url, $params = null){
		if($params != null){
			$paramsString = $this->getUrlParams($params);
		} else {
			$paramsString = "";
		}
		curl_setopt($this->curl, CURLOPT_URL, $this->base_url . $url . "/" . $paramsString);
		$data = curl_exec($this->curl);
		
		return $data;
	}
	
	public function getInfo(){
		return curl_getinfo($this->curl);
	}

	protected function getUrlParams($params){
		if(!is_array($params)){
			return false;
		}
		$urlString = "";
		foreach($params as $key=> $value){
			$urlString .= "$key/$value/";
		}
		return $urlString;
	}

	public function close(){

		if(is_resource($this->curl))
			curl_close($this->curl);
	}
}