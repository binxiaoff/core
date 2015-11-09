$(function () {
    $("#demande").change(function () {
        if ($(this).val() == 5) {
            $("#rowPreciser").slideDown();
        } else {
            $("#rowPreciser").slideUp();
        }
    });

    $("#phone").keyup(function () {
        if ($(this).val() == '') {
            $(this).removeClass('LV_invalid_field');
            $(this).removeClass('LV_valid_field')
        } else if (isNaN($(this).val())) {
            $(this).addClass("LV_invalid_field");
            $(this).removeClass('LV_valid_field');
        } else {
            $(this).removeClass('LV_invalid_field');
            $(this).addClass("LV_valid_field");
        }
    });

    $(".afficheSuivant").click(function () {
        $(".btn").show();

        var radio1 = $('input[name=radio1]:checked', '#form_inscription_preteur_etape1').val();
        $("#send_form_etape1").val(radio1);
    });

    $("#iban-1").keyup(function () {
        if ($(this).val().length == 4) {
            $("#iban-2").focus();
        }
    });
    $("#iban-2").keyup(function () {
        if ($(this).val().length == 4) {
            $("#iban-3").focus();
        }
    });
    $("#iban-3").keyup(function () {
        if ($(this).val().length == 4) {
            $("#iban-4").focus();
        }
    });
    $("#iban-4").keyup(function () {
        if ($(this).val().length == 4) {
            $("#iban-5").focus();
        }
    });
    $("#iban-5").keyup(function () {
        if ($(this).val().length == 4) {
            $("#iban-6").focus();
        }
    });
    $("#iban-6").keyup(function () {
        if ($(this).val().length == 4) {
            $("#iban-7").focus();
        }
    });

    $("#origine_des_fonds").change(function () {
        if ($(this).val() == '1000000') {
            $("#row_precision").show();
        }
        else {
            $("#row_precision").hide();
        }
    });

    $("#next_preteur_etape3").click(function () {
        if ($("#accept-cgu").attr('checked') == true) {
            $(".check").css("color", "#A1A5A7");
        }
        else {
            $(".check").css("color", "#C84747");
        }
    });

    $(".radio-holder").click(function () {
        $(".check").css("color", "#A1A5A7");
    });


});

/* Traductions */
function openTraduc(id) {
    $.fn.colorbox({href: add_url + '/thickbox/openTraduc/' + id});
}

function lisibilite_nombre(nbrall, id) {
    // on supprime les espaces
    nbrall = nbrall.replace(/ /g, "");

    nbrall = nbrall.replace(",", ".");

    // on decoupe le nombre a la virgule
    nbrall = nbrall.split(".");

    var nbr = nbrall[0];

    if (nbrall[1] != null) var virgule = "." + nbrall[1];
    else var virgule = "";

    var nombre = '' + nbr;
    var retour = '';
    var count = 0;

    for (var i = nombre.length - 1; i >= 0; i--) {
        if (count != 0 && count % 3 == 0)
            retour = nombre[i] + ' ' + retour;
        else
            retour = nombre[i] + retour;
        count++;
    }
    $("#" + id).val(retour + virgule);
}

function noNumber(val, id) {
    var nb = val.length;
    val = val.split('');
    var newval = '';

    for (i = 0; i < nb; i++) {
        if (val[i] == " ") {
            newval = newval + val[i];
        }
        if (isNaN(val[i]) == true) {
            newval = newval + val[i];
        }
    }
    $("#" + id).val(newval);
}

function noDecimale(val, id) {
    var nb = val.length;
    val = val.split('');
    var newval = '';

    for (i = 0; i < nb; i++) {
        if (val[i] != ',' && val[i] != '.') {
            newval = newval + val[i];
        }
    }
    $("#" + id).val(newval);
}

function decompte(time, id) {
    var aujourdhui = new Date();

    // on fait en sorte d'etre a lheure fr quelque soit le fuseau horaire
    var ecartFuseau = aujourdhui.getTimezoneOffset();
    aujourdhui.setHours(aujourdhui.getHours() + (ecartFuseau / 60 + 1));

    time_tmp = parseInt(aujourdhui.getTime() / 1000, 10);
    restant  = time - time_tmp;
    jour     = parseInt((restant / (60 * 60 * 24)), 10);
    heure    = parseInt((restant / (60 * 60) - jour * 24), 10);
    minute   = parseInt((restant / 60 - jour * 24 * 60 - heure * 60), 10);
    seconde  = parseInt((restant - jour * 24 * 60 * 60 - heure * 60 * 60 - minute * 60), 10);

    if (jour > 30) {
    } // on fait rien
    // si +2j ou +1j et 23h
    else if (jour >= 1 && heure > 23 || jour >= 2) {
        $('#' + id).html(jour + ' jours');
    }
    // si j-2 h-1 (1j+h23)
    else if (jour <= 1 && heure <= 23 && jour > 0 || jour == 0 && heure >= 2 || jour == 0 && heure == 1 && minute > 59) {
        var plusHeure = parseInt(jour * 24);
        var newHeure = parseInt(heure + plusHeure);

        $('#' + id).html(newHeure + ' heures');
    }
    else if (minute > 1 || heure > 0 && minute >= 0) {
        var plusMinute = parseInt(heure * 60);
        var newMinute = parseInt(minute + plusMinute);

        $('#' + id).html(newMinute + ' minutes');
    }
    //else if(minute > 0){$('#'+id).html(minute+' minutes aaaa '+heure);}
    else {
        var plusSeconde = parseInt(minute * 60);
        var newSeconde = parseInt(seconde + plusSeconde);

        $('#' + id).html(newSeconde + ' secondes');
    }

    if (time_tmp < time) {
        setTimeout(function () {
            decompte(time, id);
        }, 1000);
    } else {
        $('#' + id).html('Terminé');
    }
}

function decompteProjetDetail(time, id, lien) {
    var aujourdhui = new Date();

    // on fait en sorte d'etre a lheure fr quelque soit le fuseau horaire  // + 1 heure d'hiver - +2 heure d'ete
    var ecartFuseau = aujourdhui.getTimezoneOffset();
    aujourdhui.setHours(aujourdhui.getHours() + (ecartFuseau / 60 + 1));

    time_tmp = parseInt(aujourdhui.getTime() / 1000, 10);
    restant  = time - time_tmp;
    jour     = parseInt((restant / (60 * 60 * 24)), 10);
    heure    = parseInt((restant / (60 * 60) - jour * 24), 10);
    minute   = parseInt((restant / 60 - jour * 24 * 60 - heure * 60), 10);
    seconde  = parseInt((restant - jour * 24 * 60 * 60 - heure * 60 * 60 - minute * 60), 10);

    if (jour > 30) {
    } // on fait rien
    // si +2j ou +1j et 23h
    else if (jour >= 1 && heure > 23 || jour >= 2) {
        $('#' + id).html(jour + ' jours');
    }
    // si j-2 h-1 (1j+h23)
    else if (jour <= 1 && heure <= 23 && jour > 0 || jour == 0 && heure >= 2 || jour == 0 && heure == 1 && minute > 59) {
        var plusHeure = parseInt(jour * 24);
        var newHeure = parseInt(heure + plusHeure);

        $('#' + id).html(newHeure + ' heures');
    }
    else if (minute > 1 || heure > 0 && minute >= 0) {
        var plusMinute = parseInt(heure * 60);
        var newMinute = parseInt(minute + plusMinute);

        $('#' + id).html(newMinute + ' minutes');
    }
    //else if(minute > 0){$('#'+id).html(minute+' minutes aaaa '+heure);}
    else {
        var plusSeconde = parseInt(minute * 60);
        var newSeconde = parseInt(seconde + plusSeconde);
        if (newSeconde >= 0)
            $('#' + id).html(newSeconde + ' secondes');
    }

    if (restant > 0) {
        setTimeout(function () {
            decompteProjetDetail(time, id, lien);
        }, 1000);
    } else {
        $('#' + id).html('Terminé');
        return false;
    }
}

function checkEmail(email) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function checkConf(val, id2) {
    var val2 = $('#' + id2).val();

    if (val == val2) {
        $('#' + id2).removeClass('LV_invalid_field');
        $('#' + id2).addClass('LV_valid_field');
    } else {
        $('#' + id2).addClass('LV_invalid_field');
        $('#' + id2).removeClass('LV_valid_field');
    }
}

// check BIC
function check_bic(bic) {
    var regSWIFT = /^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/;
    if (regSWIFT.test(bic) == false) {
        $(".error_bic").slideDown();
        $("#bic").removeClass("LV_valid_field");
        $("#bic").addClass("LV_invalid_field");
        return false;
    } else {
        $(".error_bic").slideUp();
        $("#bic").removeClass("LV_invalid_field");
        $("#bic").addClass("LV_valid_field");
    }
}

// check nombre caractere iban
function check_ibanNB(id, val, nb) {
    id = "#" + id;
    if (val.length < nb) {
        $(id).addClass('LV_invalid_field');
        $(id).removeClass('LV_valid_field');
    } else {
        $(id).addClass('LV_valid_field');
        $(id).removeClass('LV_invalid_field');
    }
}

// check validation iban
function validateIban(iban) {
    var ibanValidationModulo = 97; // On utilise var au lieu de const, qui présente des incompatibilités sur IE8

    // On force les caractères alphabétiques en majuscule
    iban = iban.toUpperCase();
    // on supprime les espaces
    iban = iban.replace(new RegExp(" ", "g"), "");

    // le code iban doit faire plus de 14 caractères
    if (iban.length < 15) {
        return false;
    }

    // puis on transfert les quatre premiers caractères en fin de chaine.
    modifiedIban = iban.substring(4, iban.length) + iban.substr(0, 4);

    // On convertit les caractères alphabétiques en valeur numérique
    numericIbanString = "";
    for (var index = 0; index < modifiedIban.length; index++) {
        currentChar = modifiedIban.charAt(index);
        currentCharCode = modifiedIban.charCodeAt(index);

        // si le caractère est un digit, on le recopie
        if ((currentCharCode > 47) && (currentCharCode < 58)) {
            numericIbanString = numericIbanString + currentChar;
        }
        // si le caractère est une lettre, on le converti en valeur
        else if ((currentCharCode > 64) && (currentCharCode < 91)) {
            value = currentCharCode - 65 + 10;
            numericIbanString = numericIbanString + value;
        }
        // sinon, le code iban est invalide (caractère invalide).
        else {
            return false;
        }
    }

    // On a maintenant le code iban converti en un nombre. Un très gros nombre.
    // Tellement gros que javascript ne peut pas le gérer.
    // Pour calculer le modulo, il faut donc y aller par étapes :
    // on découpe le nombre en blocs de 5 chiffres.
    // Pour chaque bloc, on préfixe avec le modulo du bloc précédent (2 chiffres max,
    // ce qui nous fait un nombre de 7 chiffres max, gérable par javascript).
    var previousModulo = 0;
    for (var index = 0; index < numericIbanString.length; index += 5) {
        subpart = previousModulo + "" + numericIbanString.substr(index, 5);
        previousModulo = subpart % ibanValidationModulo;
    }

    return previousModulo == 1;
}

function initAutocompleteCity()
{
	$('[data-autocomplete]').each(function() {
		if($(this).data('autocomplete') == 'city' || $(this).data('autocomplete') == 'post_code' || $(this).data('autocomplete') == 'birth_city') {
			$(this).autocomplete({
				source: add_url + '/ajax/get_cities/',
				minLength: 3,

				search: function( event, ui ) {
					if ($(this).data('autocomplete') == 'birth_city'){
						$("#insee_birth").val('');
					}
				},

				select: function( event, ui ) {
					event.preventDefault();

					var myRegexp = /(.+)\s\((.+)\)/;
					var match = myRegexp.exec(ui.item.label);

					if(match != null) {
						var row = $(this).parent(".row");

						switch ($(this).data('autocomplete')) {
							case 'birth_city' :
								$(this).val(match[1]);
								$("#insee_birth").val(ui.item.value);
								break;
							case 'city' :
								$(this).val(match[1]);
								$(this).siblings("[data-autocomplete='post_code']")
                                    .val( match[2])
                                    .removeClass('LV_invalid_field')
                                    .addClass('LV_valid_field');
								break;
							case 'post_code' :
								$(this).val( match[2]);
								$(this).siblings("[data-autocomplete='city']")
                                    .val(match[1])
                                    .removeClass('LV_invalid_field')
                                    .addClass('LV_valid_field');
								break;
						}
					}
				}
			});
		}
	});
}