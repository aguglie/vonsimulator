<?php
class Interprete {
	const STRICT_SYNTAX = FALSE;
	private $pc = 0; // Program Counter
	private $code; // Codice Von Neumann caricato dall' utente
	private $operatore; // Operatore dell' istruzione in esecuzione
	private $argomento = NULL; // Argomento dell'istruzione in esecuzione
	private $commands = Array (
			"READ",
			"WRITE",
			"LOAD",
			"STORE",
			"ADD",
			"SUB",
			"MULT",
			"DIV",
			"BR",
			"BEQ",
			"BGE",
			"BG",
			"BL",
			"BLE",
			"END" 
	); // Comandi permessi
	private $commands_without_arg = Array (
			"READ",
			"WRITE",
			"END" 
	); // Comandi senza argomento
	
	/**
	 * Scorciatoia per generare un errore
	 *
	 * @param String $text        	
	 * @throws Exception
	 */
	private function errore($text) {
		throw new Exception ( "Errore Interprete: " . $text );
		exit ();
	}
	
	/**
	 * Estrapola l'operatore di Von Neumann dalla stringa data
	 *
	 * @param unknown $str        	
	 */
	private function find_operatore($str) {
		$pattern = '/(^\w{2,5}[@=]?)/';
		preg_match ( $pattern, $str, $matches );
		if (isset ( $matches [0] )) {
			$this->operatore = $matches [0];
			return true;
		} else
			return false;
	}
	
	/**
	 * Restituisce TRUE se l'operatore esiste nel linguaggio di VN.
	 *
	 * @param unknown $str        	
	 */
	private function is_valid_operatore($str) {
		$str = str_replace ( "@", "", $str ); // Elimino i due caratteri scomodi
		$str = str_replace ( "=", "", $str );
		
		/*
		 * Se l'interprete non è in modalità STRICT_SYNTAX, non c'e' bisogno
		 * di rispettare maiuscole e minuscole
		 */
		if (! STRICT_SYNTAX)
			$str = strtoupper ( $str );
		
		if (! in_array ( $str, $this->commands ))
			return false;
		else
			return true;
	}
	
	/**
	 * Restituisce l'argomento della funzione di VN
	 *
	 * @param unknown $str        	
	 */
	private function find_argomento($str) {
		$pattern = '/(-?\d{1,})/i';
		preg_match ( $pattern, $str, $matches );
		if (isset ( $matches [0] )) {
			$this->argomento = $matches [0];
			return true;
		} else
			return false;
	}
	
	/**
	 * Controlla che la sintassi della riga in analisi sia corretta
	 *
	 * @return boolean
	 */
	private function check_syntax() {
		$lines = preg_split ( "/((\r?\n)|(\r\n?))/", $this->code ); // Linee del listato
		$line = $lines [($this->pc)];
		if (! self::find_operatore ( $line )) // Cerco l'operatore nella riga selezionata
			$this->errore ( "Impossibile trovare l'operatore in riga " . $this->pc );
		
		if (! self::is_valid_operatore ( $this->operatore )) // Cerco l'operatore nella riga selezionata
			$this->errore ( "Non riesco a riconoscere l'operatore " . $this->operatore . " in riga " . $this->pc );
			
		if (! in_array ( $this->operatore, $this->commands_without_arg )) {
			// Se questo operatore ha un argomento lo cerco
			if (! self::find_argomento ( $line ))
				$this->errore ( "Impossibile trovare l'argomento per l'operatore " . $this->operatore . " in riga " . $this->pc );
		}
		return true;
	}
	
	/**
	 * Aumenta di una unità il program counter per la prossima esecuzione.
	 */
	private function increase_pc() {
		$this->pc = $this->pc + 1;
		$_SESSION ["Interprete"] ['pc'] = $this->pc;
	}
	
	/**
	 * Imposta un valore per il program counter
	 */
	private function set_pc($line) {
		$this->pc = $line;
		$_SESSION ["Interprete"] ['pc'] = $this->pc;
	}
	
	/**
	 * Esegue la funzione di Von Neumann caricata nell'oggetto
	 */
	private function exec() {
		$immediate = FALSE; // CO di tipo Immediate
		$reference = FALSE; // CO con Indirizzamento Indiretto
		$operatore = strtoupper ( $this->operatore );
		$argomento = $this->argomento;
		
		// Se nell' operatore ci sono @ o =, li tolgo per salvarli in un booleano
		if (strpos ( $operatore, "=" )) {
			$immediate = TRUE;
			$operatore = str_replace ( "=", "", $operatore ); // Rimuovo l' =
		}
		if (strpos ( $operatore, "@" )) {
			$reference = TRUE;
			$operatore = str_replace ( "@", "", $operatore ); // Rimuovo l' =
		}
		// Esecuzione vera e propria del comando
		switch ($operatore) {
			case "READ" :
				if ($immediate or $reference)
					$this->errore ( "Comando non corretto" );
				if (NastroIn::available ()) {
					// Se sul nastro di input sono presenti dei dati, li carico in accumulatore
					Accumulatore::set ( NastroIn::read () ); // ATTENZIONE: NastroIn::read cancella il contenuto una volta letto.
					$this->increase_pc ();
				} else
					NastroIn::ask_data (); // Se non c'è nulla da leggere chiedo all' utente di inserire un numero				
				break;
			
			case "WRITE" :
				if ($immediate or $reference)
					$this->errore ( "Comando non corretto" );
				NastroOut::write ( Accumulatore::get () );
				$this->increase_pc ();
				break;
			
			case "LOAD" :
				if ($immediate) {
					Accumulatore::set ( $argomento );
					$this->increase_pc ();
					break; // Abbiamo finito qui
				}
				if ($reference) {
					// Se stiamo caricando x referenza l'indirizzo reale è contenuto nella cella di memoria $argomento
					$real_address = Memoria::get ( $argomento );
					Accumulatore::set ( Memoria::get ( $real_address ) );
				} else {
					Accumulatore::set ( Memoria::get ( $argomento ) );
				}
				$this->increase_pc ();
				break;
			
			case "STORE" :
				if ($immediate)
					$this->errore ( "Comando non corretto" );
				if ($reference) {
					$real_address = Memoria::get ( $argomento );
					Memoria::set ( $real_address, Accumulatore::get () );
				} else {
					Memoria::set ( $argomento, Accumulatore::get () );
				}
				$this->increase_pc ();
				break;
			
			case "ADD" :
				if ($immediate) {
					Accumulatore::set ( (Accumulatore::get () + $argomento) );
					$this->increase_pc ();
					break;
				}
				if ($reference) {
					$real_address = Memoria::get ( $argomento );
					Accumulatore::set ( (Accumulatore::get () + Memoria::get ( $real_address )) );
				} else {
					Accumulatore::set ( (Accumulatore::get () + Memoria::get ( $argomento )) );
				}
				$this->increase_pc ();
				break;
			
			case "SUB" :
				if ($immediate) {
					Accumulatore::set ( (Accumulatore::get () - $argomento) );
					$this->increase_pc ();
					break;
				}
				if ($reference) {
					$real_address = Memoria::get ( $argomento );
					Accumulatore::set ( (Accumulatore::get () - Memoria::get ( $real_address )) );
				} else {
					Accumulatore::set ( (Accumulatore::get () - Memoria::get ( $argomento )) );
				}
				$this->increase_pc ();
				break;
			
			case "MULT" :
				if ($immediate) {
					Accumulatore::set ( (Accumulatore::get () * $argomento) );
					$this->increase_pc ();
					break;
				}
				if ($reference) {
					$real_address = Memoria::get ( $argomento );
					Accumulatore::set ( (Accumulatore::get () * Memoria::get ( $real_address )) );
				} else {
					Accumulatore::set ( (Accumulatore::get () * Memoria::get ( $argomento )) );
				}
				$this->increase_pc ();
				break;
			
			case "DIV" :
				if ($immediate) {
					if ($argomento == '0')
						$this->errore ( "Stai tentando di dividere per zero." );
					Accumulatore::set ( (( int ) (Accumulatore::get () / $argomento)) );
					$this->increase_pc ();
					break;
				}
				if ($reference) {
					$real_address = Memoria::get ( $argomento );
					Accumulatore::set ( (( int ) (Accumulatore::get () / Memoria::get ( $real_address ))) );
				} else {
					Accumulatore::set ( (( int ) (Accumulatore::get () / Memoria::get ( $argomento ))) );
				}
				$this->increase_pc ();
				break;
			
			case "BR" :
				$this->set_pc ( $argomento );
				break;
			
			case "BEQ" :
				if (Accumulatore::get () == 0) {
					$this->set_pc ( $argomento );
				} else {
					$this->increase_pc ();
				}
				break;
			
			case "BGE" :
				if (Accumulatore::get () >= 0) {
					$this->set_pc ( $argomento );
				} else {
					$this->increase_pc ();
				}
				break;
			
			case "BG" :
				if (Accumulatore::get () > 0) {
					$this->set_pc ( $argomento );
				} else {
					$this->increase_pc ();
				}
				break;
			
			case "BL" :
				if (Accumulatore::get () < 0) {
					$this->set_pc ( $argomento );
				} else {
					$this->increase_pc ();
				}
				break;
			
			case "BLE" :
				if (Accumulatore::get () <= 0) {
					$this->set_pc ( $argomento );
				} else {
					$this->increase_pc ();
				}
				break;
			
			case "END" :
				$this->set_pc ('-1');
				break;
		}
	}
	
	/**
	 * Funzione chiamata quando viene creato un oggetto Interprete
	 */
	public function Interprete() {
		// ... per ora nulla da fare
	}
	
	/**
	 * Se stavamo lavorando su un listato lo ricarico e imposto il program counter all'ultima posizione
	 *
	 * @return boolean
	 */
	public function load_code() {
		if (isset ( $_SESSION ["Interprete"] ['code'] )) {
			$this->code = $_SESSION ["Interprete"] ['code'];
			
			if (isset ( $_SESSION ["Interprete"] ['pc'] )) {
				$this->pc = $_SESSION ["Interprete"] ['pc'];
			} else
				$this->pc = 0;
			
			return true;
		} else
			$this->errore("L'interprete non ha codice su cui lavorare");
	}
	
	/**
	 * Se viene caricato del codice lo salvo e resetto il program counter
	 *
	 * @param string $code        	
	 */
	public function set_code($code) {
		$this->code = $code;
		$this->pc = 0;
		$_SESSION ["Interprete"] = Array ();
		$_SESSION ["Memoria"] = Array ();
		$_SESSION ["Accumulatore"] = Array ();
		$_SESSION ["NastroIn"] = Array ();
		$_SESSION ["NastroOut"] = Array ();
		
		$_SESSION ["Interprete"] ['code'] = $code;
		$_SESSION ["Interprete"] ['pc'] = 0;
		return true;
	}
	
	/**
	 * Restituisce la posizione attuale del program counter
	 */
	public function get_linen(){
		return $this->pc;
	}
	
	public function run($linen = '') {
		if ($linen != "" && is_numeric ( $linen )) {
			$this->pc = $linen;
			$_SESSION ["Interprete"] ['pc'] = $linen;
		}
		// Controllo di avere tutto il necessario
		if ($this->pc == '-1') return true;//Il listato e' stato eseguito fino in fondo con successo
		if (! is_numeric ( $this->pc ))
			$this->errore ( "Il program counter deve essere un numero" );
		if ($this->code == "")
			$this->errore ( "L'interprete non ha codice con cui lavorare" );
		
		$this->check_syntax (); // Controllo la sintassi, in caso di fallimento blocca lo script.
		$this->exec (); // Esegue l'istruzione, in caso di fallimento blocca lo script.
	}
}
