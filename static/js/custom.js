var timer;
var NextLine;

$(document).ready(function() {
	$("#Memoria").hide();//@TODO Bisognerebbe metterlo nel css...
	$( "#esegui" ).click(function() {
		var code = editor.getValue();
		$.post( "./server.php", { code: code })
		.done(function( data ) {
			//alert("Listato caricato");
			$("#Memoria_tbody").empty();
			timer = setTimeout(function(){ exec(); }, 1000);
		});
	});

});

var editor = ace.edit("editor");
editor.setTheme("ace/theme/twilight");
editor.session.setMode("ace/mode/ruby");
editor.setOption("firstLineNumber", parseInt(0));
editor.setFontSize(23); // will set font-size: 10px

function exec(){
	if (NextLine >= 0) {
		selectLine(NextLine);
	}
	$.getJSON( "./server.php", { exec: "1" } )
	.done(function( json ) {
		if (json.result == "OK"){//Il server risponde OK
			var ExecLine = json.message.ExecLine;
			NextLine = json.message.NextLine;
			var Asking_Data = json.message.Asking_Data;
			var Accumulatore = json.message.Accumulatore;
			var NastroOut = json.message.NastroOut;
			var Memoria = json.message.Memoria;

			if (ExecLine >= 0) {
				selectLine(ExecLine);

				if (ExecLine != NextLine){//Se non siamo in loop continuo l'esecuzione (La READ è considata un LOOP)
					timer = setTimeout(function(){ exec(); }, 1000);//Schedulo l'esecuzione della riga successiva
				}
			}
			setAccumulatore(Accumulatore);
			if (NastroOut != null){setNastroOut(NastroOut); }
			setMemoria(Memoria);
			if (ExecLine == -1){ 
				alert("Programma terminato");
			}
			if (Asking_Data){
				var data = null;
				while (data==null){
					data = prompt("Il listato chiede un valore", "57");
				}
				$.getJSON( "./server.php", { NastroIn: data } )
				.done(function( json ) {
					if (json.result == "OK"){
						timer = setTimeout(function(){ exec(); }, 1000);//Riprendo l'esecuzione
					}else alert(json.message);
				});

			}
		}else alert(json.message);
	})
	.fail(function( jqxhr, textStatus, error ) {//Il client non connette
		var err = textStatus + ", " + error;
		alert( "Request Failed: " + err );
	});
}
function selectLine(line){
	editor.selection.moveCursorToPosition({row: line, column: 0});
	editor.selection.selectLine();
}
function setAccumulatore(value){
	$('#Accumulatore').html(value);
}
function setNastroOut(value){
	$('#NastroOut').html(value);
}
function setMemoria(memoria){
	$("#MemoriaPH").hide();
	$("#Memoria").show();

	$.each( memoria, function( key, value ){
		//Verifico se la cella di memoria esiste già nell'html
		if ( $( "#memoria_" + key ).length ) {
			//Esiste, controllo se il contenuto è cambiato
			var valore_attuale = ($( "#memoria_" + key + " td:nth-child(2)").html());
			if (value == valore_attuale){
				$( "#memoria_" + key ).removeClass( "danger" );//Se non è cambiato tolgo il rosso.
			}else{
				$( "#memoria_" + key + " td:nth-child(2)").html(value);//Se è cambiato aggiorno
				$( "#memoria_" + key ).addClass( "danger" );//Se è cambiato metto il rosso.
			}
		}else{
			//Non esiste, quindi la creo
			$('#Memoria > tbody:last-child').append('<tr id="memoria_' + key + '"class="danger"><td class="col-xs-4">'+ key +'</td><td class="col-xs-8">'+ value +'</td></tr>');
		}
	});

}