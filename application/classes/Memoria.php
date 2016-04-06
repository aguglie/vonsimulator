<?php
class Memoria {
	private function errore($text) {
		throw new Exception ( "Errore Memoria: " . $text );
		exit ();
	}
	
	/**
	 * Legge dal settore di Memoria $key
	 * 
	 * @param int $key        	
	 * @return unknown
	 */
	public static function get($key) {
		if (! is_numeric ( $key ) or $key == "")
			self::errore ( "Gli indirizzi di memoria devono essere numeri!" );
		if ($key < 0)
			self::errore ( "Gli indirizzi di memoria non possono essere negativi!" );
		
		if (isset ( $_SESSION ['Memoria'] [$key] ) && is_numeric ( $_SESSION ['Memoria'] [$key] )) {
			return $_SESSION ['Memoria'] [$key];
		} else
			self::errore ( "Stai provando a leggere da un settore non inizializzato." );
	}
	
	/**
	 * Scrive $value nel settore di memoria $key
	 * 
	 * @param int $key        	
	 * @param int $value        	
	 * @return boolean
	 */
	public static function set($key, $value) {
		if (! is_numeric ( $value ))
			self::errore ( "Stai provando a scrivere un valore non numeric" );
		if (! is_numeric ( $key ))
			self::errore ( "Gli indirizzi di memoria devono essere numeri!" );
		if ($key < 0)
			self::errore ( "Gli indirizzi di memoria non possono essere negativi!" );
		$_SESSION ['Memoria'] [$key] = $value;
		return true;
	}
	
	/**
	 * Restituisce l'intera memoria
	 */
	public static function dump(){
		return $_SESSION ['Memoria'];
	}
}