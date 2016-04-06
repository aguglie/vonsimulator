<?php
class JSON {
	private $message = Array();
	
	/**
	 * Restituisce il JSON e termina lo script
	 */
	public function render() {
		$out = ( object ) Array ();
		$out->result = 'OK';
		$out->message = $this->message;
		header ( 'Content-Type: application/json' );
		echo json_encode ( $out );
		exit ();
	}
	
	/**
	 * Restituisce il JSON con l'errore e termina lo script.
	 * @param unknown $errore
	 */
	public function error($errore) {
		header ( 'Content-Type: application/json' );
		$response = ( object ) Array ();
		$response->result = 'error';
		$response->message = $errore;
		echo json_encode ( $response );
		exit ();
	}
	
	public function set($key, $value){
		$this->message[$key] = $value;
	}
}