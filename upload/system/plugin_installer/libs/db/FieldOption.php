<?php

abstract class FieldOption {
	protected $value;
	
	/**
	 * FieldOption
	 * 
	 * @access	public
	 * @param 	mixed	 $value
	 */
	public function FieldOption($value) {
		$this->value = $value;
	}
	
	/**
	 * getValue
	 * 
	 * @access	public
	 * @param	void
	 * @return	mixed
	 */
	public function getValue(){
		return $this->value;
	}
}