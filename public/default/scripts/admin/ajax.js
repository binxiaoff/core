function no_cache() {
    date_object = new Date();
    var param = date_object.getTime();

    return param;
}

function AjaxObject() {
    if (window.XMLHttpRequest) {
        xhr_object = new XMLHttpRequest();
        return xhr_object;
    }
    else if (window.ActiveXObject) {
        xhr_object = new ActiveXObject('Microsoft.XMLHTTP');
        return xhr_object;
    }
    else {
        alert('Votre navigateur ne supporte pas les objets XMLHTTPRequest...');

    }
}

/* Fonction AJAX delete image ELEMENT */
function deleteImageElement(id_elt, slug) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteImageElement' + id_elt).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteImageElement' + id_elt).innerHTML = reponse;
            document.getElementById(slug + '-old').value = '';
            document.getElementById('nom_' + slug).value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteImageElement/' + id_elt + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete fichier ELEMENT */
function deleteFichierElement(id_elt, slug) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteFichierElement' + id_elt).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteFichierElement' + id_elt).innerHTML = reponse;
            document.getElementById(slug + '-old').value = '';
            document.getElementById('nom_' + slug).value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteFichierElement/' + id_elt + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete fichier protected ELEMENT */
function deleteFichierProtectedElement(id_elt, slug) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteFichierProtectedElement' + id_elt).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteFichierProtectedElement' + id_elt).innerHTML = reponse;
            document.getElementById(slug + '-old').value = '';
            document.getElementById('nom_' + slug).value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteFichierProtectedElement/' + id_elt + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete image ELEMENT BLOC */
function deleteImageElementBloc(id_elt, slug) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteImageElementBloc' + id_elt).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteImageElementBloc' + id_elt).innerHTML = reponse;
            document.getElementById(slug + '-old').value = '';
            document.getElementById('nom_' + slug).value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteImageElementBloc/' + id_elt + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete fichier ELEMENT Bloc */
function deleteFichierElementBloc(id_elt, slug) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteFichierElementBloc' + id_elt).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteFichierElementBloc' + id_elt).innerHTML = reponse;
            document.getElementById(slug + '-old').value = '';
            document.getElementById('nom_' + slug).value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteFichierElementBloc/' + id_elt + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete fichier protected ELEMENT Bloc */
function deleteFichierProtectedElementBloc(id_elt, slug) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteFichierProtectedElementBloc' + id_elt).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteFichierProtectedElementBloc' + id_elt).innerHTML = reponse;
            document.getElementById(slug + '-old').value = '';
            document.getElementById('nom_' + slug).value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteFichierProtectedElementBloc/' + id_elt + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete image TREE */
function deleteImageTree(id_tree, lng) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteImageTree_' + lng).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteImageTree_' + lng).innerHTML = reponse;
            document.getElementById('img_menu_' + lng + '-old').value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteImageTree/' + id_tree + '/' + lng + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX delete image TREE */
function deleteVideoTree(id_tree, lng) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState != 4) {
            document.getElementById('deleteVideoTree_' + lng).innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
        }
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById('deleteVideoTree_' + lng).innerHTML = reponse;
            document.getElementById('video_' + lng + '-old').value = '';
        }
    };
    xhr_object.open('GET', add_url + '/ajax/deleteVideoTree/' + id_tree + '/' + lng + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX chargement des noms de la section de traduction */
function loadNomTexte(section) {
    if (section != "") {
        xhr_object = AjaxObject();
        var param = no_cache();

        xhr_object.onreadystatechange = function () {
            if (xhr_object.readyState != 4) {
                document.getElementById('listeNomTraduction').innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
            }
            if (xhr_object.readyState == 4 && xhr_object.status == 200) {
                var reponse = xhr_object.responseText;
                document.getElementById('btnAjouterTraduction').style.display = 'block';
                document.getElementById('btnAjouterTraduction').href = add_url + '/traductions/add/' + section;
                document.getElementById('listeNomTraduction').innerHTML = reponse;
                document.getElementById('elementTraduction').innerHTML = '';
            }
        };
        xhr_object.open('GET', add_url + '/ajax/loadNomTexte/' + section + '/' + param, true);
        xhr_object.send(null);
    }
    else {
        document.getElementById('listeNomTraduction').innerHTML = '';
        document.getElementById('elementTraduction').innerHTML = '';
        document.getElementById('btnAjouterTraduction').style.display = 'none';
    }
}

/* Fonction AJAX chargement des traductions de la section de traduction */
function loadTradTexte(nom, section) {
    if (nom != "") {
        xhr_object = AjaxObject();
        var param = no_cache();

        xhr_object.onreadystatechange = function () {
            if (xhr_object.readyState != 4) {
                document.getElementById('elementTraduction').innerHTML = '<img src="' + add_surl + '/images/admin/ajax-loader.gif">';
            }
            if (xhr_object.readyState == 4 && xhr_object.status == 200) {
                var reponse = xhr_object.responseText;
                document.getElementById('elementTraduction').innerHTML = reponse;
            }
        };
        xhr_object.open('GET', add_url + '/ajax/loadTradTexte/' + nom + '/' + section + '/' + param, true);
        xhr_object.send(null);
    }
    else {
        document.getElementById('elementTraduction').innerHTML = '';
    }
}

/* Activer un utilisateur sur une zone */
function activeUserZone(id_user, id_zone, zone) {
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function () {
        if (xhr_object.readyState == 4 && xhr_object.status == 200) {
            var reponse = xhr_object.responseText;
            document.getElementById(zone).src = reponse;
        }
    };
    xhr_object.open('GET', add_url + '/ajax/activeUserZone/' + id_user + '/' + id_zone + '/' + param, true);
    xhr_object.send(null);
}

/* Fonction AJAX change le statut d'un dossier*/
function check_status_dossier(surl, status, id_project) {
    if (status == 40) {
        var message = 'valider';
    }
    else if (status == 30) {
        var message = 'rejeter';
    }


    if (confirm('Etes vous sur de ' + message + ' le dossier ?') == true) {
        xhr_object = AjaxObject();
        var param = no_cache();

        var date_pub = document.getElementById('date_pub').value;
        var date_pub = date_pub.replace(/\//g, "-");

        xhr_object.onreadystatechange = function () {
            if (xhr_object.readyState == 4 && xhr_object.status == 200) {
                var reponse = xhr_object.responseText;
                if (reponse == 'nok') {
                    alert('Tous les critères obligatoires n\'ont pas été rentrés');
                }
                else {
                    document.getElementById('current_statut').innerHTML = reponse;
                    $('#status_dossier').remove();
                }
            }
        };
        xhr_object.open('GET', add_url + '/ajax/check_status_dossier/' + status + '/' + id_project + '/' + date_pub + '/' + param, false);
        xhr_object.send(null);

    }
}


function addMemo(id, type) {
    var content_memo = $('#content_memo').val();
    var val = {content_memo: content_memo, id: id, type: type};
    $.post(add_url + '/ajax/addMemo', val).done(function (data) {
        $("#table_memo").html(data);
    });
}

function deleteMemo(id_project_comment, id_project) {
    if (confirm('Etes vous sur de vouloir supprimer ?') == true) {
        var val = {id_project_comment: id_project_comment, id_project: id_project};
        $.post(add_url + '/ajax/deleteMemo', val).done(function (data) {
            $("#table_memo").html(data);
        });
    }
}

// Creation du client apres saisi de l'email dans l'etape 2 de la creation de dossier

function create_client(id_project) {
    var val = {email: $("#email_etape2").val(), id_client: $("#id_client").val(), id_project: id_project};
    $.post(add_url + '/ajax/create_client', val).done(function (data) {

        obj = jQuery.parseJSON(data);

        var error = obj.error;
        if (error == 'nok') {
            $("#email_etape2").css('border-color', 'red');
            $("#email_etape2").css('color', 'red');

            $("#sav_email2").show();
            $("#sav_etape2").hide();

            $("#valid_end").show();
            $("#end_create").hide();
        }
        else {
            var id_client = obj.id_client;

            $("#email_etape2").css('border-color', '#2F86B2');
            $("#email_etape2").css('color', '#2F86B2');

            $("#id_client").val(id_client);
            $("#sav_email2").hide();
            $("#sav_etape2").show();

            $("#valid_end").hide();
            $("#end_create").show();
        }
    });
}

function valid_etape2(id_project) {
    var has_prescripteur = $('#enterprise3_etape2').attr('checked'),
        val = 'id_project=' + id_project + '&etape=2&has_prescripteur=' + has_prescripteur + '&' + $('#dossier_etape2').serialize();

    if (false === has_prescripteur) {
        $("#civilite_prescripteur").html('');
        $("#prenom_prescripteur").html('');
        $("#nom_prescripteur").html('');
        $("#email_prescripteur").html('');
        $("#telephone_prescripteur").html('');
    }

    $.post(add_url + '/ajax/valid_etapes', val).done(function(data) {
        $("#title").val($("#raison_sociale_etape2").val());
        $("#prenom").val($("#prenom_etape2").val());
        $("#nom").val($("#nom_etape2").val());

        if ($("#same_address_etape2").attr('checked') == 1) {
            $("#adresse").val($("#address_etape2").val());
            $("#city").val($("#ville_etape2").val());
            $("#zip").val($("#postal_etape2").val());
            $("#phone").val($("#phone_etape2").val());
        } else {
            $("#adresse").val($("#adresse_correspondance_etape2").val());
            $("#city").val($("#city_correspondance_etape2").val());
            $("#zip").val($("#zip_correspondance_etape2").val());
            $("#phone").val($("#phone_correspondance_etape2").val());
        }

        $("#valid_etape2").slideDown();

        setTimeout(function () {
            $("#valid_etape2").slideUp();
        }, 3000);
    });
}

function valid_etape3(id_project) {
    var val = {
        montant_etape3:      $("#montant_etape3").val(),
        duree_etape3:        $("#duree_etape3").val(),
        titre_etape3:        $("#titre_etape3").val(),
        objectif_etape3:     $("#objectif_etape3").val(),
        presentation_etape3: $("#presentation_etape3").val(),
        moyen_etape3:        $("#moyen_etape3").val(),
        comments_etape3:     $("#comments_etape3").val(),
        id_project:          id_project,
        etape:               3
    };

    $.post(add_url + '/ajax/valid_etapes', val).done(function(data) {
        $("#montant").val($("#montant_etape3").val());
        $("#montant_etape1").val($("#montant_etape3").val());
        $('#duree option[value="' + $("#duree_etape3").val() + '"]').attr('selected', true);
        $('#duree_etape1 option[value="' + $("#duree_etape3").val() + '"]').attr('selected', true);
        $("#title").val($("#titre_etape3").val());
        $("#valid_etape3").slideDown();

        setTimeout(function () {
            $("#valid_etape3").slideUp();
        }, 3000);

    });
}

function valid_etape4_1(id_project) {
    var val = 'id_project=' + id_project + '&etape=4.1&' + $('#dossier_etape4_1').serialize();

    $.post(add_url + '/ajax/valid_etapes', val).done(function(data) {
        $("#valid_etape4_1").slideDown();

        setTimeout(function () {
            $("#valid_etape4_1").slideUp();
        }, 3000);
    });
}

function valid_etape4_2(id_project) {
    var val = 'id_project=' + id_project + '&etape=4.2&' + $('#dossier_etape4_2').serialize();

    $.post(add_url + '/ajax/valid_etapes', val).done(function(data) {
        $("#valid_etape4_2").slideDown();

        setTimeout(function () {
            $("#valid_etape4_2").slideUp();
        }, 3000);
    });
    return false;
}

function valid_etape6(id_project) {
    var val = {
        question1:  $("#question1").val(),
        question2:  $("#question2").val(),
        question3:  $("#question3").val(),
        id_project: id_project,
        etape:      6
    };

    $.post(add_url + '/ajax/valid_etapes', val).done(function(data) {
        $("#valid_etape6").slideDown();

        setTimeout(function () {
            $("#valid_etape6").slideUp();
        }, 3000);
    });
}

function recapdashboard(month, annee) {
    var val = {
        month: month,
        annee: annee
    };

    $.post(add_url + '/ajax/recapdashboard', val).done(function (data) {
        $("#recapDashboard").html(data);
    });
}

function ratioDashboard(month, annee) {
    var val = {
        month: month,
        annee: annee
    };

    $.post(add_url + '/ajax/ratioDashboard', val).done(function (data) {
        $("#ratioDashboard").html(data);
    });
}

function generer_le_mdp(id_client) {

    var val = {
        id_client: id_client
    };
    $.post(add_url + '/ajax/generer_mdp', val).done(function (data) {
        if (data != 'nok') {

            $(".reponse").slideDown();

            setTimeout(function () {
                $(".reponse").slideUp();
            }, 3000);
        }
    });
}

function send_email_borrower_area(id_client, type) {

    var val = {
        id_client: id_client,
        type: type
    };
    $.post(add_url + '/ajax/send_email_borrower_area', val).done(function (data) {
        if (data != 'nok') {

            $(".reponse_email").slideDown();

            setTimeout(function () {
                $(".reponse_email").slideUp();
            }, 3000);
        }
    });
}

function loadDashYear(annee) {
    var val = {
        annee: annee
    };
    $.post(add_url + '/ajax/loadDashYear', val).done(function (data) {
        if (data != 'nok') {

            $(".contentLoadYear").html(data);

        }
    });
}

function check_status_dossierV2(status, id_project) {
    if (status == 25) var message = 'passer en revue';
    else if (status == 30) var message = 'rejeter';

    if (confirm('Etes vous sur de ' + message + ' le dossier ?') == true) {
        $.post(add_url + '/ajax/check_status_dossierV2', {
            status: status,
            id_project: id_project
        }).done(function (data) {
            if (data != 'nok') {
                var obj = jQuery.parseJSON(data),
                    liste = obj.liste,
                    etape_6 = obj.etape_6;

                $('#analysts-row').show();
                $('#current_statut').html(liste);
                $('#status_dossier').remove();
                $('#content_etape6').html(etape_6);
            }
            else if (data == 'nok') {
                alert('Tous les critères obligatoires n\'ont pas été rentrés');
            }
        });
    }
}


function nodizaines(val, id) {
    val = parseFloat(val.replace(',', '.'));
    var long = val.length;
    if (val > 10) {
        alert('Vous devez renseigner un chiffre inférieur à 10');
        $("#" + id).val('0');
    }
}

function valid_rejete_etape6(status, id_project) {
    if (status == 1) var message = 'valider';
    else if (status == 2) var message = 'rejeter';
    else if (status == 3) var message = 'sauvegarder';

    if (confirm('Etes vous sur de ' + message + ' le dossier ?') == true) {

        var structure = parseFloat($('#structure').val().replace(',', '.'));
        var rentabilite = parseFloat($('#rentabilite').val().replace(',', '.'));
        var tresorerie = parseFloat($('#tresorerie').val().replace(',', '.'));

        var individuel = parseFloat($('#individuel').val().replace(',', '.'));
        var global = parseFloat($('#global').val().replace(',', '.'));

        var performance_fianciere = parseFloat($('#performance_fianciere').html().replace(',', '.'));
        var marche_opere = parseFloat($('#marche_opere').html().replace(',', '.'));
        var qualite_moyen_infos_financieres = parseFloat($('#qualite_moyen_infos_financieres').val().replace(',', '.'));
        var notation_externe = parseFloat($('#notation_externe').val().replace(',', '.'));
        var avis = ckedAvis.getData();

        var form_ok = true;


        if (isNaN(structure) != false && structure || isNaN(rentabilite) != false || isNaN(tresorerie) != false || isNaN(performance_fianciere) != false || isNaN(individuel) != false || isNaN(global) != false || isNaN(marche_opere) != false || isNaN(qualite_moyen_infos_financieres) != false || isNaN(notation_externe) != false) {
            form_ok = false;
            alert('Vous devez renseigner un chiffre infèrieur ou égale à 10 dans les 7 premiers champs');
        }
        else if (structure > 10 || rentabilite > 10 || tresorerie > 10 || performance_fianciere > 10 || individuel > 10 || global > 10 || marche_opere > 10 || qualite_moyen_infos_financieres > 10 || notation_externe > 10 || structure == 0 || rentabilite == 0 || tresorerie == 0 || performance_fianciere == 0 || individuel == 0 || global == 0 || marche_opere == 0 || qualite_moyen_infos_financieres == 0 || notation_externe == 0) {

            if (status == 1) {
                form_ok = false;
                alert('Vous devez renseigner un chiffre infèrieur ou égale à 10');
            }
        }
        else if (avis.length < 50 && status == 1) {
            form_ok = false;
            alert('Vous devez renseigner un avis (50 caractères minimum)');
        }

        if (form_ok == true) {
            $.post(add_url + '/ajax/valid_rejete_etape6', {
                status: status,
                id_project: id_project,
                structure: structure,
                rentabilite: rentabilite,
                tresorerie: tresorerie,
                performance_fianciere: performance_fianciere,
                global: global,
                individuel: individuel,
                marche_opere: marche_opere,
                qualite_moyen_infos_financieres: qualite_moyen_infos_financieres,
                notation_externe: notation_externe,
                avis: avis
            }).done(function (data) {

                if (data != 'nok') {

                    // Arrondis
                    $('#structure').val(Math.round(structure * 10) / 10);
                    $('#rentabilite').val(Math.round(rentabilite * 10) / 10);
                    $('#tresorerie').val(Math.round(tresorerie * 10) / 10);

                    $('#global').val(Math.round(global * 10) / 10);
                    $('#individuel').val(Math.round(individuel * 10) / 10);

                    $('#performance_fianciere').html(Math.round(performance_fianciere * 10) / 10);
                    $('#marche_opere').html(Math.round(marche_opere * 10) / 10);
                    $('#qualite_moyen_infos_financieres').val(Math.round(qualite_moyen_infos_financieres * 10) / 10);
                    $('#notation_externe').val(Math.round(notation_externe * 10) / 10);

                    var obj = jQuery.parseJSON(data);
                    var liste = obj.liste;
                    var etape_7 = obj.etape_7;

                    $('#valid_etape6').slideDown();

                    setTimeout(function () {
                        $("#valid_etape6").slideUp();
                    }, 3000);

                    $('#current_statut').html(liste);

                    if (status != 3) {
                        $('.btnValid_rejet_etape6').remove();

                        if (status == 1) {

                            if ($('#content_etape7').html() == '') {
                                $('#content_etape7').html(etape_7);
                            }
                            else {
                                $('#content_etape7').show();
                                $('.btnValid_rejet_etape7').show();
                            }
                        }
                    }
                }

            });
        }


    }
}

function valid_rejete_etape7(status, id_project) {
    if (status == 1) var message = 'valider';
    else if (status == 2) var message = 'rejeter';
    else if (status == 3) var message = 'sauvegarder';
    else if (status == 4) var message = 'vouloir plus d\'informations sur';

    if (confirm('Etes vous sur de ' + message + ' le dossier ?') == true) {
        // Variables
        var structure = parseFloat($('#structure2').val().replace(',', '.'));
        var rentabilite = parseFloat($('#rentabilite2').val().replace(',', '.'));
        var tresorerie = parseFloat($('#tresorerie2').val().replace(',', '.'));

        var global = parseFloat($('#global2').val().replace(',', '.'));
        var individuel = parseFloat($('#individuel2').val().replace(',', '.'));

        var performance_fianciere = parseFloat($('#performance_fianciere2').html().replace(',', '.'));
        var marche_opere = parseFloat($('#marche_opere2').html().replace(',', '.'));
        var qualite_moyen_infos_financieres = parseFloat($('#qualite_moyen_infos_financieres2').val().replace(',', '.'));
        var notation_externe = parseFloat($('#notation_externe2').val().replace(',', '.'));

        //var note = parseFloat($('#moyenneNote_etape7').val().replace(',','.'));
        var avis_comite = ckedAvis_comite.getData();

        var form_ok = true;

        if (isNaN(structure) != false || isNaN(rentabilite) != false || isNaN(tresorerie) != false || isNaN(performance_fianciere) != false || isNaN(individuel) != false || isNaN(global) != false || isNaN(marche_opere) != false || isNaN(qualite_moyen_infos_financieres) != false || isNaN(notation_externe) != false) {
            form_ok = false;
            alert('Vous devez renseigner un chiffre infèrieur ou égale à 10 dans les 7 premiers champs');
        }
        else if (structure > 10 || rentabilite > 10 || tresorerie > 10 || performance_fianciere > 10 || individuel > 10 || global > 10 || marche_opere > 10 || qualite_moyen_infos_financieres > 10 || notation_externe > 10 || structure == 0 || rentabilite == 0 || tresorerie == 0 || performance_fianciere == 0 || individuel == 0 || global == 0 || marche_opere == 0 || qualite_moyen_infos_financieres == 0 || notation_externe == 0) {
            if (status == 1) {
                form_ok = false;
                alert('Vous devez renseigner un chiffre infèrieur ou égale à 10');
            }
        }
        else if (avis_comite.length < 50 && status == 1) {
            form_ok = false;
            alert('Vous devez renseigner un avis (50 caractères minimum)');
        }

        if (form_ok == true) {
            $.post(add_url + '/ajax/valid_rejete_etape7', {
                status: status,
                id_project: id_project,
                avis_comite: avis_comite,
                structure: structure,
                rentabilite: rentabilite,
                tresorerie: tresorerie,
                performance_fianciere: performance_fianciere,
                global: global,
                individuel: individuel,
                marche_opere: marche_opere,
                qualite_moyen_infos_financieres: qualite_moyen_infos_financieres,
                notation_externe: notation_externe
            }).done(function (data) {


                if (data != 'nok') {
                    var obj = jQuery.parseJSON(data);
                    var liste = obj.liste;
                    var btn_etape6 = obj.btn_etape6;
                    var risk = obj.content_risk;

                    $('#valid_etape7').slideDown();

                    setTimeout(function () {
                        $("#valid_etape7").slideUp();
                    }, 3000);

                    $('#current_statut').html(liste);

                    if (status != 3) {
                        if (status == 4)$('.btnValid_rejet_etape7').hide();
                        else $('.btnValid_rejet_etape7').remove();

                        $('.btnValid_rejet_etape6').remove();
                    }
                    // Plus d'infos
                    if (status == 4) {
                        $('#content_etape7').hide();
                        $('.listBtn_etape6').html(btn_etape6);
                    }
                    // valide
                    else if (status == 1) {
                        $('.content_risk').html(risk);
                        $('.content_risk').show();
                        $('.change_statut').show();
                        $('.content_date_publicaion').show();
                        $('.content_date_retrait').show();

                        var recharge = '<script type="text/javascript">$("#status").change(function() { if($("#status").val() == 40){ $(".change_statut").hide();}else{$(".change_statut").show();}});</script>';

                        $('.recharge').html(recharge);
                    }
                }
            });
        }
    }
}

/* fonction qui vérifie la force d'un mot de passe */
function check_force_pass() {
    xhr_object = AjaxObject();
    var param = no_cache();

    new_pass = document.getElementById('new_pass').value;

    // On traite les donnees en POST via l'ajax
    xhr_object.open('POST', add_url + '/ajax/check_force_pass', false);
    xhr_object.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr_object.send('pass=' + new_pass);
    // On recupere la reponse
    var reponse = xhr_object.responseText;
    // Si la reponse est OK on balance l'ajax
    document.getElementById('indicateur_force').innerHTML = reponse;
}


/* Fonction qui check si une autre compagnie possede deja cet iban */
function CheckIfIbanExistDeja(iban, bic, id_client) {
    xhr_object = AjaxObject();
    var param = no_cache();

    // On traite les donnees en POST via l'ajax
    xhr_object.open('POST', add_url + '/ajax/ibanExistV2', false);
    xhr_object.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr_object.send('iban=' + iban + '&id=' + id_client + '&bic=' + bic);
    // On recupere la reponse
    var reponse = xhr_object.responseText;
    // Si la reponse est OK on balance l'ajax
    return reponse;
}
