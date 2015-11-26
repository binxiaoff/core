//*************************//
// *** FICHIER JS ADMIN ***//
//*************************//

/* Elements Jquery */
$(document).ready(function()
{
    $(".thickbox").colorbox();
});

/* Changer l'onglet de la langue */
function changeOngletLangue(lng)
{
    var lang_encours = document.getElementById('lng_encours').value;

    document.getElementById('lng_encours').value = lng;
    document.getElementById('lien_'+lang_encours).className = '';
    document.getElementById('lien_'+lng).className = 'active';
    document.getElementById('langue_'+lang_encours).style.display = 'none';
    document.getElementById('langue_'+lng).style.display = 'block';
}

/* Set du nouvel ID parent pour les 2 langues */
function setNewIdParent(id)
{
    document.getElementById('id_parent').value = id;
}

/* Changer l'onglet de la page produit */
function changeOngletProduit(divon,divoff)
{
    document.getElementById('lien_'+divoff).className = '';
    document.getElementById('lien_'+divon).className = 'active';
    document.getElementById(divoff).style.display = 'none';
    document.getElementById(divon).style.display = 'block';
}
/* Chack formulaire edition user */
function checkFormModifUser()
{
    if(document.getElementById('email').value == '')
    {
        alert("Vous devez indiquer une adresse e-mail !");
        return false;
    }

    return true;
}

/* Check du formulaire d'ajout d'un user */
function checkFormAjoutUser()
{
    if(document.getElementById('email').value == '')
    {
        alert("Vous devez indiquer une adresse e-mail !");
        return false;
    }
    else if(document.getElementById('password').value == '')
    {
        alert("Vous devez indiquer un mot de passe !");
        return false;
    }

    return true;
}

/* Ajout détail produit */
function ajouterDetails(div)
{
    var nb_encours = parseInt(document.getElementById(div).value);

    // Attribution du nouveau nombre
    document.getElementById(div).value = nb_encours + 1;
    var nb_new = parseInt(document.getElementById(div).value);

    // Affichage de la ligne correspondante
    document.getElementById('contenuDetails'+nb_new).style.display = 'block';

    if(nb_new == 10)
    {
        document.getElementById('lienAjoutDetails').style.display = 'none';
    }
}

// check BIC
function check_bic(bic)
{
    var regSWIFT = /^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/;
    if(regSWIFT.test(bic) == false){

        return false;
    }
    else{
        return true
    }
}

// check nombre caractere iban
function check_ibanNB(id,val,nb){
    id = "#"+id;
    if(val.length < nb){$(id).addClass('LV_invalid_field');$(id).removeClass('LV_valid_field');}
    else{$(id).addClass('LV_valid_field');$(id).removeClass('LV_invalid_field');}
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
    modifiedIban = iban.substring(4, iban.length)+iban.substr(0,4);

    // On convertit les caractères alphabétiques en valeur numérique
    numericIbanString = "";
    for (var index = 0; index < modifiedIban.length; index ++) {
        currentChar = modifiedIban.charAt(index);
        currentCharCode = modifiedIban.charCodeAt(index);

        // si le caractère est un digit, on le recopie
        if ((currentCharCode > 47) && (currentCharCode <  58)) {
            numericIbanString = numericIbanString + currentChar;
        }
        // si le caractère est une lettre, on le converti en valeur
        else if ((currentCharCode > 64) && (currentCharCode < 91)) {
            value = currentCharCode-65+10;
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
        subpart = previousModulo+""+numericIbanString.substr(index, 5);
        previousModulo = subpart % ibanValidationModulo;
    }

    return previousModulo == 1;
}

function initAutocompleteCity(elmCity, elmCode)
{
    $([elmCode, elmCity]).each(function() {
        var getBirthPlace= '';
        if (elmCity.data('autocomplete') == 'birth_city') {
            getBirthPlace = 'birthplace/';
        }
        $(this).autocomplete({
            source: add_url + '/ajax/get_cities/' + getBirthPlace,
            minLength: 3,

            search: function( event, ui ) {
                if ($(this).data('autocomplete') == 'birth_city'){
                    elmCode.val('');
                }
            },

            select: function( event, ui ) {
                event.preventDefault();

                var myRegexp = /(.+)\s\((.+)\)/;
                var match = myRegexp.exec(ui.item.label);

                if(match != null) {
                    elmCity.val(match[1]);
                    if ($(this).data('autocomplete') == 'birth_city'){
                        elmCode.val(ui.item.value);
                    } else {
                        elmCode.val(match[2]);
                    }
                }
            }
        });
    });
}