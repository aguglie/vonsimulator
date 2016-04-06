<?php
class NastroIn extends Nastro {
	
	/**
	 * Risponde TRUE se sul NastroIN è stato scritto qualcosa ed è numerico
	 */
	public static function available() {
		if (isset ( $_SESSION ['NastroIn'] ['buffer'] ) && is_numeric ( $_SESSION ['NastroIn'] ['buffer'] )) {
			return true;
		} else
			return false;
	}
	
	/**
	 * Restituisce il numero salvato in NastroIn
	 */
	public static function read() {
		if (isset ( $_SESSION ['NastroIn'] ['buffer'] ) && is_numeric ( $_SESSION ['NastroIn'] ['buffer'] )) {
			$out = $_SESSION ['NastroIn'] ['buffer'];
			$_SESSION ['NastroIn'] = Array (); // Reinizializzo il NastroIn
			return $out;
		} else
			self::errore ( " ERRORE DEL SERVER, Sto provando a leggere da un settore non inizializzato." );
	}
	
	/**
	 * Scrive un valore nel nastro di ingresso
	 * @param unknown $str
	 */
	public static function set($str) {
		if (is_numeric($str) && $str != ""){
			$_SESSION ['NastroIn'] ['buffer'] = $str;
			$_SESSION ['NastroIn'] ['ask_data'] = FALSE;
			return true;
		}else self::errore("Il valore scritto nel nastro deve essere un numero");
	}
	
	/**
	 * Chiede a video di inserire dei dati nel nastro
	 */
	public static function ask_data() {
		$_SESSION ['NastroIn'] ['ask_data'] = TRUE;
		return true;
	}
	
	public static function asking_data() {
		if ($_SESSION ['NastroIn'] ['ask_data'] === TRUE) return true;
		else false;
	}
}