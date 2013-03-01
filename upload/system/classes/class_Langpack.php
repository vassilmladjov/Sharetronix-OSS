<?php
class Langpack  {
	
	protected $langkey;
	protected $lang_name;
	protected $version;
	protected $folder;
	
	public function Langpack($langkey, $connect = TRUE){
		global $C;
		$this->langkey = $connect? $langkey : $langkey->abrv;
		$this->folder = $C->PROJPATH . "system/languages/" . $this->langkey;
		
		global $C;
		if(isset($C->STX_USERNAME) == false || isset($C->STX_PASSWORD) == false) {
			throw new Exception("No appstore login data is available");
		}

		if( $connect ){
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();
			
			$data = $api->get('apilanguage/getLanguage', array('langkey' => $this->langkey));
			
			$info = $api->getInfo();
			$api->close();
			
			if($info['http_code'] != 200) {
				throw new Exception("Could not retrieve lang file data");
			}
			
			$lang =  json_decode($data);
		
		}else{
			$lang = $langkey;	
			
		}

		$this->buildObject($lang);
	}
	
	
	/**
	 * getSupportedLanguages
	 * 
	 * @access	public
	 * @param	void
	 * @throws 	Exception
	 * @return	array<Langpack>
	 */
	public static function getSupportedLanguages(){
		global $C;
		$user = & $GLOBALS['user'];
		
		if(isset($C->STX_USERNAME) == false || isset($C->STX_PASSWORD) == false) {
			throw new Exception("No appstore login data is available");
		}
		
		$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
		$api->open();
		
		$data = $api->get('apilanguage/getAllAvailable');

		$info = $api->getInfo();
		$api->close();

		if($info['http_code'] == 200 && $info['content_type'] == 'application/json') {
			$langs = json_decode($data);
			$langObjects = array();
			
			foreach($langs as $l) {
				$lang = new Langpack($l, FALSE); 
				$lang->buildObject($l);
				$langObjects[] = $lang;
			}
			
			return $langObjects;
		} else {
			throw new Exception("An error occured while trying to connect to marketplace: HTTP_CODE:" . $info['http_code']);
		}
		
	}
	
	/**
	 * getInstalledLanguages
	 * 
	 * @access	public
	 * @param	void
	 * @return	array<Langpack>
	 */
	public static function getInstalledLanguages(){
		$db = & $GLOBALS['db2'];
		$sql = "
			SELECT langkey, version 
			FROM languages 
			WHERE installed = 1 
		";
		$res = $db->fetch_all($sql);
		if(!empty($res)) {
			$langs = array();
			
			foreach($res as $r) {
				$langs[] = $r;
			}
			
			return $langs;
			
		}
		return array();
		
	}
	
	
	/**
	 * getNumUpgradableLangs
	 * 
	 * @access	public
	 * @param	void
	 * @return	int
	 */
	public static function getNumUpgradableLangs()
	{	
		$num_upgradable = 0;
		$installed = self::getInstalledLanguages(); 	
		$available_associative =  self::getSupportedLanguagesAssociative();
		
		foreach($installed as $lang){ 
			if( isset($available_associative[$lang->langkey]) && $lang->version < $available_associative[$lang->langkey]){ 
				$num_upgradable++;
			}
		}
		
		return $num_upgradable;
	}
	
	protected static function getSupportedLanguagesAssociative()
	{
		$available = self::getSupportedLanguages();
		$available_edited = array();
		
		foreach( $available as $key=>$value ){
			$available_edited[$value->langkey] = $value->version;
		}
		
		return $available_edited;
	}
	
	protected function buildObject($data){
		global $C;
		
		$this->langkey = $data->abrv;
		$this->lang_name = $data->lang_name;
		$this->version = $data->version;
	}
	
	
	public function isInstalled(){
		$db = & $GLOBALS['db2'];
		$sql = "
			SELECT * 
			FROM languages 
			WHERE langkey = '" . $this->langkey .  "' 
			LIMIT 1
		";
		$res = $db->fetch($sql);
		if(!empty($res)) {
			return (bool)$res->installed;
		}
		return false;
		
	}
	
	public function isUpgradable(){
		$db = & $GLOBALS['db2'];
		$sql = "
			SELECT * 
			FROM languages 
			WHERE langkey = '" . $this->langkey . "'
			AND installed = 1 
			AND version < " . $this->version . "
			LIMIT 1  
		";
		$res = $db->fetch($sql);
		if(!empty($res)){
			return true;
		}
		return false;
	}
	
	
	public function install(){
	
		global $C;
		
		$langfile = $this->getFile(); 
		$this->clearLanguageFolder();
		$langfolder = $this->extractLangpack($langfile);
		
		$from = $langfolder . "/" . $this->langkey;
		$to = $C->PROJPATH . "system/languages/" . $this->langkey;
		if(!is_dir($to)) {
			mkdir($to, 0777, true);
		}
		
		rcopy($from, $to);
		
		$sql = "
			INSERT INTO languages (
				langkey, installed, `version`
			) VALUES (
				'" . $this->langkey . "', 1, " . $this->version . "
			) 
			ON DUPLICATE  KEY
				UPDATE installed = 1, `version` = " . $this->version . "
		";

		$db = & $GLOBALS['db2'];
		$db->query($sql);
		 
	}
	
	public function upgrade(){
		global $C;
		
		$langfile = $this->getFile();
		$this->clearLanguageFolder();
		$langfolder = $this->extractLangpack($langfile);
		
		$from = $langfolder . "/" . $this->langkey;
		$to = $C->PROJPATH . "system/languages/" . $this->langkey;
		if(!is_dir($to)) {
			mkdir($to, 0777, true);
		}
		
		rcopy($from, $to);
		
		$sql = "
			UPDATE languages 
			SET installed = 1, `version` = " . $this->version . "
			WHERE langkey = '" . $this->langkey . "'
		";
		
		$db = & $GLOBALS['db2'];
		$db->query($sql);
	}
	
	
	public function remove(){
		
		$this->clearLanguageFolder();
		rmdir($this->folder);
		$sql = "
			DELETE FROM languages 
			WHERE langkey = '" . $this->langkey . "'
		";
		$db = & $GLOBALS['db2'];
		$db->query($sql);
	}
	
	private function getFile(){
		global $C;
		
		// get file
		$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
		$api->open();
		
		$data = $api->get('apilanguage/getLanguageFile', array("langkey" => $this->langkey));
		$info = $api->getInfo();
		if($info['http_code'] !== 200) {
			throw new Exception("An error occured while trying to fetch the language pack file");
		}
		
		$file = base64_decode($data);
		
		$lang_file = $C->PROJPATH . "system/tmp/" . md5(time()) . ".zip";
		@file_put_contents($lang_file, $file);
		
		$api->close();
		
		return $lang_file;
	}
	
	
	private function clearLanguageFolder(){
		if(is_dir($this->folder) ) {
			rrmdir($this->folder);
		}
	}
	
	
	private function extractLangpack($file){
		global $C;
		$tmp_folder = $C->PROJPATH . "system/tmp/" . md5(time()+1);
		
		if(is_dir($tmp_folder) == false)
			mkdir($tmp_folder, 0777, true);

		
		$zip = new ZipArchive();
		if($zip->open($file) === true)
		{
			$zip->extractTo($tmp_folder);
			$zip->close();
		}
		else
		{
			throw new Exception("Error: failed to extract the plugin archive");
		}
		
		return $tmp_folder;
	}
	
	
	public function __get($var){
		if(isset($this->$var))  {
			return $this->$var;
		} else { 
			throw new Exception("Cannot find such object property");
		}
	}
	
	public function toArray(){
		$data = $this;
		return get_object_vars($data);
	}
	
}

if( function_exists("rcopy") == false || function_exists("rrmdir") == false ) {
	require_once $C->INCPATH . 'helpers/func_filesystem.php';
}
