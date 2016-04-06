<?php
class NastroOut extends Nastro {
	/**
	 * Scrive il numero in NastroOut
	 */
	public static function write($str) {
		$_SESSION ['NastroOut'] ['buffer'] = $str;
		return true;
	}
	
	public static function get(){
		return $_SESSION ['NastroOut'] ['buffer'];
	}
}