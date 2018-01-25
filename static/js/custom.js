//Variabili globali
var timer;//Timer verso l'esecuzione della prox riga
var NextLine;//Prossima da eseguire
var ExecLine;//Correntemente in esecuzione
var UploadNeeded = true;//devo caricare il codice sul server
var editor = ace.edit("editor");
editor.setTheme("ace/theme/twilight");
editor.session.setMode("ace/mode/ruby");
editor.setOption("firstLineNumber", parseInt(0));
editor.setFontSize(23); // will set font-size: 10px
editor.on("change", function () {
    UploadNeeded = true;
});//Se il codice viene alterato va ricaricato


$(document).ready(function () {
    $("#Memoria").hide();//@TODO Bisognerebbe metterlo nel css...

    if(/chrom(e|ium)/.test(navigator.userAgent.toLowerCase())){
        intro();
    }else{
        alert("Ciao, ti consiglio di utilizzare il sito dal PC utilizzando GoogleChrome");
    }
    //Gestione pulsantoni:
    $("#esegui").click(function () {
        clearInterval(timer);
        loadCode(function(){
          timer = setInterval(function () {
              exec(true);
          }, 1000);
        });
    });

    $("#esegui_step").click(function () {
        clearInterval(timer);
        loadCode(function(){ exec(false) });
    });

});

/**
 * Carica il codice sul server se non è aggiornato ed esegue la callback
 */
function loadCode(callback) {
    if (UploadNeeded) {
        UploadNeeded = false;
        var code = editor.getValue();
        $.post("./server.php", {code: code})
            .done(function (data) {
                //Pulisco la memoria ed eseguo la callback
                $("#Memoria_tbody").empty();
                if (callback && (typeof callback == "function")) {
                  callback();
                }
            });
    }else{
      //Se non c'è niente da fare esego subito la callback
      if (callback && (typeof callback == "function")) {
        callback();
      }
    }
}

/**
 * Fa avanzare l'esecuzione
 * @boolean topBottom: identifica se l'esecuzione è stepbystep
 */
function exec(topBottom) {//topBottom
    if (NextLine >= 0) {
        selectLine(NextLine);//Seleziono la riga che sto x eseguire
    }
    $.getJSON("./server.php", {exec: "1"})
        .done(function (json) {
            if (json.result == "OK") {//Il server risponde OK
                ExecLine = json.message.ExecLine;
                NextLine = json.message.NextLine;
                var Asking_Data = json.message.Asking_Data;
                var Accumulatore = json.message.Accumulatore;
                var NastroOut = json.message.NastroOut;
                var Memoria = json.message.Memoria;

                //Aggiorno la grafica
                setAccumulatore(Accumulatore);
                if (NastroOut != null) {
                    setNastroOut(NastroOut);
                }

                setMemoria(Memoria);

                if (ExecLine == -1) {//L'istruzione END è stata eseguita
                    clearInterval(timer);
                    fine_listato();
                }

                if (ExecLine >= 0) {
                    selectLine(ExecLine);
                    if (ExecLine == NextLine) {//Se siamo in loop o in attesa di una READ blocco tutto
                        clearInterval(timer);
                    }
                }

                if (Asking_Data) {//Se il server richiede dei dati mostro un popup
                    clearInterval(timer);
                    var data = null;
                    $.confirm({
                        title: 'Nastro in Ingresso',
                        keyboardEnabled: true,
                        confirmButton: 'Invia',
                        confirmButtonClass: 'btn-success',
                        content: 'url:./static/html/NastroIn.html',
                        confirm: function () {
                            var input = this.$b.find('input#input-modal');
                            var errorText = this.$b.find('.text-danger');
                            if (input.val() == '') {
                                errorText.show();
                                return false;
                            } else {
                                //Se ho un dato presunto valido lo comunico al serben
                                data = input.val();
                                setAccumulatore(data);
                                $.getJSON("./server.php", {NastroIn: data})
                                    .done(function (json) {
                                        if (json.result == "OK") {
                                            if (topBottom) {//Se non siamo in step-by-step bisogna riavviare il timer
                                                timer = setInterval(function () {
                                                    exec(true);
                                                }, 1000);
                                            }
                                        } else errore(json.message);
                                    });
                            }
                        }
                    });
                }
            } else errore(json.message);
        })
        .fail(function (jqxhr, textStatus, error) {//Il client non connette
            var err = textStatus + ", " + error;
            errore("Request Failed: " + err);
        });
}

/**
 * Seleziona la linea nel box di testo
 * @param line
 */
function selectLine(line) {
    editor.selection.moveCursorToPosition({row: line, column: 0});
    editor.selection.selectLine();
}

/**
 * Aggiorna l'accumulatore
 * @param value
 */
function setAccumulatore(value) {
    $('#Accumulatore').html(value);
}

/**
 * Aggiorna il nastro d'uscita
 * @param value
 */
function setNastroOut(value) {
    $('#NastroOut').html(value);
}

/**
 * Aggiorna la tabella della memoria
 * @param memoria
 */
function setMemoria(memoria) {
    $("#MemoriaPH").hide();
    $("#Memoria").show();

    $.each(memoria, function (key, value) {
        //Verifico se la cella di memoria esiste già nell'html
        if ($("#memoria_" + key).length) {
            //Esiste, controllo se il contenuto è cambiato
            var valore_attuale = ($("#memoria_" + key + " td:nth-child(2)").html());
            if (value == valore_attuale) {
                $("#memoria_" + key).removeClass("danger");//Se non è cambiato tolgo il rosso.
            } else {
                $("#memoria_" + key + " td:nth-child(2)").html(value);//Se è cambiato aggiorno
                $("#memoria_" + key).addClass("danger");//Se è cambiato metto il rosso.
            }
        } else {
            //Non esiste, quindi la creo
            $('#Memoria > tbody:last-child').append('<tr id="memoria_' + key + '"class="danger"><td class="col-xs-4">' + key + '</td><td class="col-xs-8">' + value + '</td></tr>');
        }
    });

}

/**
 * Mostra a video un messaggio d'errore e resetta i timers
 * @param msg
 */
function errore(msg) {
    clearInterval(timer);
    $.alert({
        title: 'Ops...',
        content: msg,
        confirmButton: 'Okay',
        confirmButtonClass: 'btn-primary',
        icon: 'fa fa-info',
        animation: 'zoom'
    });
}

/**
 * A fine listato chiede se voglio resettare la MdV
 */
function fine_listato() {
    $.confirm({
        title: 'Perfetto!',
        content: 'Il listato &egrave; stato intepretato con successo,<br/>Se vuoi eseguire nuovamente il codice, resetta la macchina.',
        confirmButton: 'Resetta Macchina',
        confirmButtonClass: 'btn-danger',
        cancelButton: 'Ok',
        cancelButtonClass: 'btn-info',
        icon: 'fa fa-question-circle',
        animation: 'scale',
        animationClose: 'top',
        opacity: 0.5,
        confirm: function () {
            UploadNeeded = true;
        }
    });
}

/**
 * Breve intro all'apertura
 */
function intro(){
    clearInterval(timer);
    $.alert({
        title: 'Ciao',
        content: 'Questo simulatore della Macchina di VonNeumann &egrave; del tutto sperimentale, <u>se qualcosa non funziona segnalamelo</u>',
        confirmButton: 'Capito',
        confirmButtonClass: 'btn-success',
        icon: 'fa fa-info'
    });
}
