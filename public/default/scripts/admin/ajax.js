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

function editMemo(projectId, commentId) {
    $.ajax({
        url: add_url + '/dossiers/memo',
        method: 'POST',
        dataType: 'html',
        data: {
            projectId: projectId,
            commentId: commentId,
            content: $('#content_memo').val()
        },
        success: function(response) {
            $('#table_memo').html(response)
            $.fn.colorbox.close()
        }
    });
}

function deleteMemo(projectId, commentId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer le mémo ?')) {
        var memoRows = $('#table_memo .tablesorter tbody tr'),
            targetedMemoRow = event.target

        $.ajax({
            url: add_url + '/dossiers/memo/' + projectId + '/' + commentId,
            method: 'DELETE',
            dataType: 'json',
            success: function(response) {
                if (response.success != undefined && response.success) {
                    if (memoRows.length == 1) {
                        $('#table_memo *').remove()
                    } else {
                        $(targetedMemoRow).closest('tr').remove()
                    }
                } else {
                    if (response.error != undefined && response.error) {
                        if (response.message != undefined) {
                            alert(response.message)
                        } else {
                            alert('Erreur inconnue')
                        }
                    }
                }
            }
        })
    }
}

function valid_etape1(id_project) {
    $("#dossier_etape1").find(".btn_link").hide();

    var val = {
        montant_etape1: $("#montant_etape1").val(),
        duree_etape1: $("#duree_etape1").val(),
        siren_etape1: $("#siren_etape1").val(),
        source_etape1: $("#source_etape1").val(),
        partner_etape1: $("#partner_etape1").val(),
        id_project: id_project,
        etape: 1
    };

    $.post(add_url + '/ajax/valid_etapes', val).done(function (data) {
        if ('OK' == data) {
            $(location).attr('href', add_url + '/dossiers/edit/' + id_project);
            return;
        }
        $("#siren").val($("#siren_etape1").val());
        $("#montant").val($("#montant_etape1").val());
        $('#duree option[value="' + $("#duree_etape1").val() + '"]').prop('selected', true);

        $("#dossier_etape1").find(".btn_link").show();
        $("#valid_etape1").html(data);
        $("#valid_etape1").slideDown();

        if (0 == $("#duree_etape1").val()) {
            $("#status").css('display', 'none');
            $("#msgProject").css('display', 'none');
            $("#displayPeriodHS").css('display', 'block');
            $("#msgProjectPeriodHS").css('display', 'block');
        } else {
            $("#status").css('display', 'block');
            $("#msgProject").css('display', 'block');
            $("#displayPeriodHS").css('display', 'none');
            $("#msgProjectPeriodHS").css('display', 'none');
        }
        setTimeout(function () {
            $("#valid_etape1").slideUp();
        }, 3000);

    });
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
    var has_prescripteur = $('#enterprise3_etape2').prop('checked'),
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

        if ($("#same_address_etape2").prop('checked')) {
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

function valid_etape4_1(id_project) {
    var val = 'id_project=' + id_project + '&etape=4.1&' + $('#dossier_etape4_1').serialize();

    $.post(add_url + '/ajax/valid_etapes', val).done(function(data) {
        $("#valid_etape4_1").slideDown();

        setTimeout(function () {
            $("#valid_etape4_1").slideUp();
        }, 3000);
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

function check_status_dossier(status, id_project) {
    if (status == 30) {
        var isNotBalanced = false;

        if ($('#total_actif_0').data('total') != $('#total_passif_0').data('total')) {
            $('#total_actif_0').css('background-color', '#f00');
            $('#total_passif_0').css('background-color', '#f00');
            isNotBalanced = true;
        }

        if ($('#total_actif_1').data('total') != $('#total_passif_1').data('total')) {
            $('#total_actif_1').css('background-color', '#f00');
            $('#total_passif_1').css('background-color', '#f00');
            isNotBalanced = true;
        }

        if ($('#total_actif_2').data('total') != $('#total_passif_2').data('total')) {
            $('#total_actif_2').css('background-color', '#f00');
            $('#total_passif_2').css('background-color', '#f00');
            isNotBalanced = true;
        }

        if (isNotBalanced) {
            alert('Certains comptes ne sont pas équilibrés');
            location.hash = '#section-balance-sheets'
            return;
        }
    }

    if (status == 30) {
        var message = 'passer en revue';
    } else if (status == 25) {
        var message = 'rejeter';
    } else {
        console.log('Valeur inconnue', status);
        return;
    }

    if (confirm('Êtes-vous sûr de ' + message + ' le dossier ?') == true) {
        $.post(add_url + '/ajax/check_status_dossier', {
            status: status,
            id_project: id_project,
            rejection_reason: $('#rejection_reason option:selected').val()
        }).done(function (data) {
            if (data != 'nok') {
                location.reload()
            } else if (data == 'nok') {
                alert('Tous les critères obligatoires n\'ont pas été rentrés')
            }
            parent.$.fn.colorbox.close()
        })
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

    if (confirm('Êtes-vous sûr de ' + message + ' le dossier ?') == true) {
        var structure                       = parseFloat($('#structure').val().replace(',', '.')),
            rentabilite                     = parseFloat($('#rentabilite').val().replace(',', '.')),
            tresorerie                      = parseFloat($('#tresorerie').val().replace(',', '.')),
            individuel                      = parseFloat($('#individuel').val().replace(',', '.')),
            global                          = parseFloat($('#global').val().replace(',', '.')),
            performance_fianciere           = parseFloat($('#performance_fianciere').html().replace(',', '.')),
            marche_opere                    = parseFloat($('#marche_opere').html().replace(',', '.')),
            dirigeance                      = parseFloat($('#dirigeance').val().replace(',', '.')),
            indicateur_risque_dynamique     = parseFloat($('#indicateur_risque_dynamique').val().replace(',', '.')),
            avis                            = ckedAvis.getData(),
            rejection_reason                = $('#rejection_reason option:selected').val(),
            form_ok                         = true;

        if (isNaN(structure) != false && structure || isNaN(rentabilite) != false || isNaN(tresorerie) != false || isNaN(performance_fianciere) != false || isNaN(individuel) != false || isNaN(global) != false || isNaN(marche_opere) != false || isNaN(dirigeance) != false || isNaN(indicateur_risque_dynamique) != false) {
            form_ok = false;
            alert('Vous devez renseigner un chiffre infèrieur ou égale à 10 dans les 7 premiers champs');
        }
        else if (structure > 10 || rentabilite > 10 || tresorerie > 10 || performance_fianciere > 10 || individuel > 10 || global > 10 || marche_opere > 10 || dirigeance > 10 || indicateur_risque_dynamique > 10 || structure == 0 || rentabilite == 0 || tresorerie == 0 || performance_fianciere == 0 || individuel == 0 || global == 0 || marche_opere == 0 || dirigeance == 0 || indicateur_risque_dynamique == 0) {
            if (status == 1) {
                form_ok = false;
                alert('Vous devez renseigner un chiffre infèrieur ou égale à 10');
            }
        }
        else if (avis.length < 50 && status == 1) {
            form_ok = false;
            alert('Vous devez renseigner un avis (50 caractères minimum)');
        }
        else if (status == 2 && rejection_reason == '') {
            form_ok = false;
            alert('Vous devez renseigner le motif de rejet');
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
                dirigeance: dirigeance,
                indicateur_risque_dynamique: indicateur_risque_dynamique,
                avis: avis,
                rejection_reason: rejection_reason
            }).done(function(data) {
                var response = jQuery.parseJSON(data)

                if (response.success) {
                    if (status == 3) {
                        $('#valid_etape6').slideDown()

                        setTimeout(function() {
                            $('#valid_etape6').slideUp()
                        }, 3000)
                    } else {
                        location.reload()
                    }
                } else if (response.error) {
                    alert(response.error)
                } else {
                    alert('Une erreur est survenue')
                }
            });
        }
    }
}

function valid_rejete_etape7(status, id_project) {
    var validation_message = '',
        rate_message       = '';
    if (status == 1) {
        if ($('#min_rate').val() != '' && $('#max_rate').val() != '' && typeof $('#min_rate').val() != "undefined" && typeof $('#max_rate').val() != "undefined") {
            var min_rate     = $('#min_rate').val(),
                max_rate     = $('#max_rate').val(),
                rate_message = '\nTaux (min / max) indicatif : ' + min_rate + ' % / ' + max_rate + ' %';
        }
        var message            = 'valider',
            note_comite        = $('span.moyenneNote_comite').text(),
            validation_message = 'Note comité : ' + note_comite + ' \nMontant du projet : ' + $('#montant').val() + ' euros \nDurée du projet : ' + $('#duree').val() + ' mois' + rate_message + '\n';

    }
    else if (status == 2) var message = 'rejeter';
    else if (status == 3) var message = 'sauvegarder';
    else if (status == 4) var message = 'vouloir plus d\'informations sur';

    if (confirm(validation_message + 'Êtes-vous sûr de ' + message + ' le dossier ?') == true) {
        var structure                       = parseFloat($('#structure_comite').val().replace(',', '.')),
            rentabilite                     = parseFloat($('#rentabilite_comite').val().replace(',', '.')),
            tresorerie                      = parseFloat($('#tresorerie_comite').val().replace(',', '.')),
            global                          = parseFloat($('#global_comite').val().replace(',', '.')),
            individuel                      = parseFloat($('#individuel_comite').val().replace(',', '.')),
            performance_fianciere           = parseFloat($('#performance_fianciere_comite').html().replace(',', '.')),
            marche_opere                    = parseFloat($('#marche_opere_comite').html().replace(',', '.')),
            dirigeance                      = parseFloat($('#dirigeance_comite').val().replace(',', '.')),
            indicateur_risque_dynamique     = parseFloat($('#indicateur_risque_dynamique_comite').val().replace(',', '.')),
            avis_comite                     = ckedAvis_comite.getData(),
            rejection_reason                = $('#rejection_reason option:selected').val(),
            form_ok = true;

        if (isNaN(structure) != false || isNaN(rentabilite) != false || isNaN(tresorerie) != false || isNaN(performance_fianciere) != false || isNaN(individuel) != false || isNaN(global) != false || isNaN(marche_opere) != false || isNaN(dirigeance) != false || isNaN(indicateur_risque_dynamique) != false) {
            form_ok = false;
            alert('Vous devez renseigner un chiffre infèrieur ou égale à 10 dans les 7 premiers champs');
        }
        else if (structure > 10 || rentabilite > 10 || tresorerie > 10 || performance_fianciere > 10 || individuel > 10 || global > 10 || marche_opere > 10 || dirigeance > 10 || indicateur_risque_dynamique > 10 || structure == 0 || rentabilite == 0 || tresorerie == 0 || performance_fianciere == 0 || individuel == 0 || global == 0 || marche_opere == 0 || dirigeance == 0 || indicateur_risque_dynamique == 0) {
            if (status == 1) {
                form_ok = false;
                alert('Vous devez renseigner un chiffre infèrieur ou égale à 10');
            }
        }
        else if (avis_comite.length < 50 && status == 1) {
            form_ok = false;
            alert('Vous devez renseigner un avis (50 caractères minimum)');
        }
        else if (status == 2 && rejection_reason == '') {
            form_ok = false;
            alert('Vous devez renseigner le motif de rejet');
        }
        else if (status == 1) {
            if ($.isNumeric($('#duree').val()) == false || $('#duree').val() <= 0) {
                form_ok = false;
                alert('Vous devez renseigner la durée du prêt');
            }
            else if ($('#montant').val() <= 0) {
                form_ok = false;
                alert('Vous devez renseigner le montant du prêt');
            }
        }

        if (form_ok == true) {
            $.post(add_url + '/ajax/valid_rejete_etape7', {
                status: status,
                id_project: id_project,
                avis_comite: avis_comite,
                structure_comite: structure,
                rentabilite_comite: rentabilite,
                tresorerie_comite: tresorerie,
                performance_fianciere_comite: performance_fianciere,
                global_comite: global,
                individuel_comite: individuel,
                marche_opere_comite: marche_opere,
                dirigeance_comite: dirigeance,
                indicateur_risque_dynamique_comite: indicateur_risque_dynamique,
                rejection_reason: rejection_reason
            }).done(function(data) {
                var response = jQuery.parseJSON(data)

                if (response.success) {
                    if (status == 3) {
                        $('#valid_etape7').slideDown()

                        setTimeout(function() {
                            $('#valid_etape7').slideUp()
                        }, 3000)
                    } else {
                        location.reload()
                    }
                } else if (response.error) {
                    alert(response.error)
                } else {
                    alert('Une erreur est survenue')
                }
            })
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
