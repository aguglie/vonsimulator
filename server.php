<?php
// Autoload funzioni
spl_autoload_register ( function ($class) {
	include realpath ( __DIR__ . '/application/classes/' . $class . '.php' );
} );
session_start ();

/**
 * Se viene spedito del codice via POST lo salvo e termino.
 */
if (isset ( $_REQUEST ['code'] )) {
	$_SESSION = Array ();
	$interprete = new Interprete ();
	$interprete->set_code ( $_POST ['code'] );
	exit ();
}

/**
 * Se viene richiesta dal client l'esecuzione del listato.
 */
if (isset ( $_REQUEST ['exec'] )) {
	$json = new JSON (); // Oggetto JSON che rispedisco al client
	$interprete = new Interprete ();
	try {
		$interprete->load_code ();
		$exec_line = (int)$interprete->get_linen ();
		$interprete->run ();
	} catch ( Exception $e ) {
		$json->error ( $e->getMessage () );
		exit ();
	}
	// Spedisco al client lo stato aggiornato delle variabili
	try {
		// L'accumulatore non può essere letto se vuoto
		$json->set ( "Accumulatore", Accumulatore::get () );
	} catch ( Exception $e ) {
	}
	$json->set ( "NastroOut", NastroOut::get () );
	$json->set ( "Memoria", Memoria::dump () );
	$json->set ( "Asking_Data", NastroIn::asking_data () );
	$json->set ( "ExecLine", $exec_line );
	$json->set ( "NextLine", (int)$interprete->get_linen () );
	$json->render ();
	exit ();
}

/**
 * Salva i valori del nastro in ingresso
 */
if (isset ( $_REQUEST ['NastroIn'] )) {
	$json = new JSON ();
	try {
		NastroIn::set ( $_REQUEST ['NastroIn'] );
	} catch ( Exception $e ) {
		$json->error ( $e->getMessage () );
		exit ();
	}
	$json->render ();
}
