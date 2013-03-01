<?php



if(defined("PROJPATH") == false){
	chdir(__DIR__);
	chdir("../..");
	$projpath = getcwd();
	
	define('PROJPATH', $projpath);
}
define("PLUGINDIR", PROJPATH . "apps" . DIRECTORY_SEPARATOR);

include PROJPATH . 'system/conf_main.php';
$C->INSTALLER_PATH = $C->INCPATH . "plugin_installer" . DIRECTORY_SEPARATOR;


require_once $C->INSTALLER_PATH . 'libs/db/DatabaseInterface.php';
require_once $C->INSTALLER_PATH . 'libs/validation/DatabaseValidate.php';
require_once $C->INSTALLER_PATH . 'libs/db/DatabaseReal.php';
require_once $C->INSTALLER_PATH . 'libs/db/ActiveRecord.php';

require_once $C->INSTALLER_PATH . 'libs/TableOwner.php';

require_once $C->INSTALLER_PATH . 'libs/db/ColumnType.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOption.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOptionLimit.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOptionDefault.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOptionNull.php';



abstract class Installer {

	const INSTALLER_MODE_VALIDATION=1;
	const INSTALLER_MODE_INSTALL=0;
	
	protected $db;
	public static $mode;
	public static $manifest;
	
	public function Installer(){
		
		if(self::$mode === null) 
			throw new Exception("Something is terribly wrong; no install mode set!");
		
		
			
		if(self::$mode == Installer::INSTALLER_MODE_INSTALL) {
			$this->db = new DatabaseReal(self::$manifest->plugin_name);
		} else {
			
			$this->db = new DatabaseValidate(self::$manifest->plugin_name);
		}
	}
	
	abstract public function up();
	abstract public function down();
	
	
	final public function finish(){
		
		if(self::$mode == Installer::INSTALLER_MODE_VALIDATION) {
			return $this->db->getResult();
		} else {
			//@TODO: check this
			//print "Installation finished from parrent";
		}
		
	}
	
}

if( function_exists("rcopy") == false || function_exists("rrmdir") == false ) {
	require_once $C->INCPATH . 'helpers/func_filesystem.php';
}
