//***************************//
// *** FICHIER AJAX ADMIN ***//
//***************************//

function no_cache()
{
    date_object = new Date();
    var param = date_object.getTime();

    return param;
}

function AjaxObject()
{
    if(window.XMLHttpRequest)
    {
        xhr_object = new XMLHttpRequest();
        return xhr_object;
    }
    else if(window.ActiveXObject)
    {
        xhr_object = new ActiveXObject('Microsoft.XMLHTTP');
        return xhr_object;
    }
    else
    {
        alert('Votre navigateur ne supporte pas les objets XMLHTTPRequest...');
        return;
    }
}

/* Modification de la modifcation des traductions à la volée */
function activeModificationsTraduction(etat,url)
{
    xhr_object = AjaxObject();
    var param = no_cache();

    xhr_object.onreadystatechange = function()
    {
        if(xhr_object.readyState == 4 && xhr_object.status == 200)
        {
            var reponse = xhr_object.responseText;
            document.location.href = url;
        }
    }
    xhr_object.open('GET',add_url + '/ajax/activeModificationsTraduction/' + etat + '/' + param ,true);
    xhr_object.send(null);
}

function session_etape2_lender()
{

    var radio1 = $('input[name=radio1]:checked', '#form_inscription_preteur_etape2').val();

    var val = {
        bic: $("#bic").val(),
        iban1: $("#iban-1").val(),
        iban2: $("#iban-2").val(),
        iban3: $("#iban-3").val(),
        iban4: $("#iban-4").val(),
        iban5: $("#iban-5").val(),
        iban6: $("#iban-6").val(),
        iban7: $("#iban-7").val(),
        origine_des_fonds: $("#origine_des_fonds").val(),
        cni_passeport: radio1,
        preciser: $("#preciser").val()
    }
    $.post(add_url + '/ajax/session_etape2_lender', val).done(function(data) {
        //alert(data);

    });
}

function autocompleteCp(laval,id_cp)
{
    var val = {
        ville: laval
    }
    $.post(add_url + '/ajax/autocompleteCp', val).done(function(data) {

        if(data != 'nok')
        {
            $("#"+id_cp).val(data);
        }
    });
}

function favori(id_project,id,id_client,page)
{
    $.post(add_url + '/ajax/favori', {id_project: id_project,id_client: id_client}).done(function(data) {

        if(data != 'nok')
        {
            if(data== 'create')
            {
                $('#'+id).addClass('active');
                if(page != 0)$('#'+id).html('Retirer de mes favoris <i></i>');
            }
            else if(data=='delete')
            {
                $('#'+id).removeClass('active');
                if(page != 0)$('#'+id).html('Ajouter à mes favoris <i></i>')
            }
        }

    });
}

function load_transac(year,id_client)
{
    var val = {
        year: year,
        id_client: id_client
    }
    $.post(add_url + '/ajax/load_transac', val).done(function(data) {

        if(data != 'nok')
        {
            $(".transac").html(data);
        }
    });
}

function load_finances(year,id_lender)
{

    var val = {
        year: year,
        id_lender: id_lender
    }
    $.post(add_url + '/ajax/load_finances', val).done(function(data) {

        if(data != 'nok')
        {
            $(".finances").html(data);
        }
    });
}

function transfert() {
    if ($("#mot-de-passe").val() != '' && $("#montant").val() != '') {
        var val = {
            mdp: $("#mot-de-passe").val(),
            montant: $("#montant").val()
        };

        $.post(add_url + '/ajax/transfert', val).done(function (data) {
            if (data != 'nok') {
                if (data == 'noMdp') {
                    $("#mot-de-passe").addClass('LV_invalid_field');
                    $("#mot-de-passe").removeClass('LV_valid_field');
                }
                else {
                    $("#mot-de-passe").addClass('LV_valid_field');
                    $("#mot-de-passe").removeClass('LV_invalid_field');
                }

                if (data == 'noMontant' || data == 'noMontant2' || data == 'noMontant3') {
                    $("#montant").addClass('LV_invalid_field');
                    $("#montant").removeClass('LV_valid_field');
                }
                else {
                    $("#montant").addClass('LV_valid_field');
                    $("#montant").removeClass('LV_invalid_field');
                }

                if (data == 'noMontant3') {
                    $(".error_montant_offre").show();
                } else {
                    $(".error_montant_offre").hide();
                }

                if (data == 'noBic' || data == 'noIban') {
                    $(".noBicIban").slideDown();
                }

                if (data == 'ok') {
                    $(".reponse").slideDown();

                    $.post(add_url + '/ajax/solde').done(function (data) {
                        if (data != 'nok') {
                            $("#solde").html(data);
                        }
                    });
                }
            }
        });
    }
    else {
        if ($("#mot-de-passe").val() == '') {
            $("#mot-de-passe").addClass('LV_invalid_field');
            $("#mot-de-passe").removeClass('LV_valid_field');
        }
        else {
            $("#mot-de-passe").addClass('LV_valid_field');
            $("#mot-de-passe").removeClass('LV_invalid_field');
        }

        if ($("#montant").val() == $("#montant").attr('title')) {
            $("#montant").addClass('LV_invalid_field');
            $("#montant").removeClass('LV_valid_field');
        }
        else {
            $("#montant").addClass('LV_valid_field');
            $("#montant").removeClass('LV_invalid_field');
        }
    }
}

// fonction controle mdp
function controleMdp(mdp,id,async, idConfirmation)
{
    async = typeof async !== 'undefined' ? async : true;
    var result = false;

    $.ajax({
        url: add_url+"/ajax/complexMdp",
        data: { mdp: mdp },
        method: 'POST',
        async: async
    }).done(function(data){
        if(data == 'ok'){
            $("#"+id).removeClass("LV_invalid_field");$("#"+id).addClass("LV_valid_field");
            $("#"+idConfirmation).addClass("LV_invalid_field");
            result = true;
        } else{
            $("#"+id).removeClass("LV_valid_field");$("#"+id).addClass("LV_invalid_field");
            result = false;
        }
    });
    return result;
}

// fonction controle mdp
function acceptCookies()
{
    $.post( add_url+"/ajax/acceptCookies").done(function( data ) {

        var obj = jQuery.parseJSON(data);

        if(obj.reponse == true){
            $('.cookies').slideUp();
            setTimeout(function(){ $( ".cookies" ).remove(); }, 3000);

        }
        else{

        }
    });
}

function controleCity(elmCity, elmCountry, async)
{
    async = typeof async !== 'undefined' ? async : true;
    var result = false;
    $.ajax({
        url: add_url + '/ajax/checkCity/' + elmCity.val() + '/' + elmCountry.val(),
        method: 'GET',
        async: async
    }).done(function(data){
        if (data == 'ok') {
            elmCity.addClass('LV_valid_field').removeClass('LV_invalid_field');
            result = true;
        } else {
            elmCity.addClass('LV_invalid_field').removeClass('LV_valid_field');
            result = false;
        }
    });

    return result;
}

function controlePostCodeCity(elmCp, elmCity, elmCountry, async)
{
    async = typeof async !== 'undefined' ? async : true;
    var result = false;

    if ('' == elmCp.val() || '' == elmCity.val()) {
        elmCp.addClass('LV_invalid_field').removeClass('LV_valid_field');
        elmCity.addClass('LV_invalid_field').removeClass('LV_valid_field');
        return result;
    }

    $.ajax({
        url: add_url + '/ajax/checkPostCodeCity/' + elmCp.val() + '/' + elmCity.val() + '/' + elmCountry.val(),
        method: 'GET',
        async: async
    }).done(function(data){
        if (data == 'ok') {
            elmCp.addClass('LV_valid_field').removeClass('LV_invalid_field');
            elmCity.addClass('LV_valid_field').removeClass('LV_invalid_field');
            result = true;
        } else {
            elmCp.addClass('LV_invalid_field').removeClass('LV_valid_field');
            elmCity.addClass('LV_invalid_field').removeClass('LV_valid_field');
            result = false;
        }
    });

    return result;
}

function appendProjects() {
    var offset = $('.unProjet:last').offset();

    if ((offset.top - $(window).height() <= $(window).scrollTop())
        && ($('.unProjet').size() >= 10) &&
        ($('.unProjet').size() != $('.nbProjet').text())) {

        $(window).off("scroll");

        var last_id = $('.unProjet:last').attr('id');

        $('.loadmore').show();

        var val = {
            last: last_id,
            positionStart: $('#positionStart').html(),
            ordreProject: $('#ordreProject').html(),
            where: $('#where').html(),
            type: $('#valType').html()
        };
        $.ajax({
            url: add_url + '/ajax/load_project/',
            type: 'GET',
            data: val,
            dataType: 'json',
            success: function(obj) {
                if (obj.hasMore == true) {
                    var positionStart = obj.positionStart;
                    var affichage = obj.affichage;

                    $('.unProjet:last').after(affichage);
                    offset = $('.unProjet:last').offset();
                    $(window).scroll(appendProjects);

                    $('#positionStart').html(positionStart);
                }
                $('.loadmore').fadeOut(500);
            }
        });
    }
}
