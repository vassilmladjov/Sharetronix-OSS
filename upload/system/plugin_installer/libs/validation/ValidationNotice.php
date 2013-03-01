<?php
require_once $C->INSTALLER_PATH . 'libs/validation/ValidationMessage.php';

class ValidationNotice  extends ValidationMessage{
	
	/**
	 * ValidationNotice
	 * 
	 * @access	public
	 * @param 	string	 $message
	 */
	public function ValidationNotice($message) {
		$this->message = $message;
	}
}