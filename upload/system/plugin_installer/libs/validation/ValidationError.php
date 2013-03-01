<?php

require_once $C->INSTALLER_PATH . 'libs/validation/ValidationMessage.php';

class ValidationError extends ValidationMessage{
	
	/**
	 * ValidationError
	 * 
	 * @access	public
	 * @param 	string	 $message
	 */
	public function ValidationError($message) {
		$this->message = $message;
	}
}
