<?php
class Accumulatore {
	private function errore($text) {
		throw new Exception ( "Errore Accumulatore: " . $text );
		exit ();
	}
	
	/**
	 * Scrive dati sull'accumulatore
	 * 
	 * @param int $str        	
	 */
	public static function set($str) {
		$str = intval($str);//Altrimenti lo 0 viene letto come FALSE
		if (! is_numeric ( $str )) {
			self::errore ( "Non posso salvare " . $str . ", non e' un numerico." );
			return false;
		}
		$_SESSION ['Accumulatore'] ['value'] = $str;
		return true;
	}
	
	/**
	 * Recupera dati dall' accumulatore
	 */
	public static function get() {
		if (! isset ( $_SESSION ['Accumulatore'] ['value'] )) {
			self::errore ( "Impossibile leggere dall'accumulatore, non l'hai ancora inizializzato." );
			return false;
		}
		return $_SESSION ['Accumulatore'] ['value'];
	}
}