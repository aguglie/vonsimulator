<?php
class Nastro {
	// Qui vanno le funzioni e variabili comuni sia al Nastro di ingresso che di uscita
	protected function errore($text) {
		throw new Exception ( "Errore Nastro: " . $text );
		exit ();
	}
}