<?php

abstract class ValidationMessage {
	protected $message;

	/**
	 * getMessage
	 * 
	 * @access	public
	 * @param	void
	 * @return	string	
	 */
	public function getMessage(){
		return $this->message;
	}
}