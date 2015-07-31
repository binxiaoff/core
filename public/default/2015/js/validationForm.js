$(function () {

    $('input').keydown(function(){
        $(this).removeClass('error');
    });
    $('#inscription_date_naissance').keydown(function(){
        $('#errorAge').html('');
    });
    $('select').change(function(){
        $(this).next('.c2-sb-wrap').removeClass('error');
    });
    $('#inscription_cgv').change(function(){
        $(this).parent().find('label').removeClass('error');
    });
    $('#errorAge').html('');

    var civilite = '';
    var nom = '';
    var prenom = '';
    var email = '';

    $("button#inscription_submit2").click(function() {
        $("button#inscription_submit2").addClass("clicked");
        $("button#voir_projets").removeClass("clicked");
    });
    $("button#voir_projets").click(function() {
        $("button#voir_projets").addClass("clicked");
        $("button#inscription_submit2").removeClass("clicked");
    });

    $('#form_inscription').submit(function(event) {
        event.preventDefault();

        $('html, body').animate({
            scrollTop: 0
        }, 1000, 'swing');

        var inscription_civilite = $('#inscription_civilite').val();
        var inscription_nom = $.trim($('#inscription_nom').val());
        var inscription_prenom = $.trim($('#inscription_prenom').val());
        var inscription_email = $.trim($('#inscription_email').val());

        var inscription_mdp = $.trim($('#inscription_mdp').val());
        var inscription_mdp2 = $.trim($('#inscription_mdp2').val());
        var inscription_question = $.trim($('#inscription_question').val());
        var inscription_reponse = $.trim($('#inscription_reponse').val());
        var inscription_adresse_fiscale = $.trim($('#inscription_adresse_fiscale').val());
        var inscription_ville_fiscale = $.trim($('#inscription_ville_fiscale').val());
        var inscription_cp_fiscale = $.trim($('#inscription_cp_fiscale').val());
        var inscription_id_pays_fiscale = $('#inscription_id_pays_fiscale').val();
        // var inscription_check_adresse = $('#inscription_check_adresse').val();
        var inscription_adresse_correspondance = $.trim($('#inscription_adresse_correspondance').val());
        var inscription_ville_correspondance = $.trim($('#inscription_ville_correspondance').val());
        var inscription_cp_correspondance = $.trim($('#inscription_cp_correspondance').val());
        var inscription_id_pays_correspondance = $('#inscription_id_pays_correspondance').val();
        var inscription_telephone = $.trim($('#inscription_telephone').val());
        var inscription_id_nationalite = $('#inscription_id_nationalite').val();
        var inscription_date_naissance = $('#inscription_date_naissance').val();
        var inscription_commune_naissance = $.trim($('#inscription_commune_naissance').val());
        var inscription_id_pays_naissance = $('#inscription_id_pays_naissance').val();
        var inscription_cgv = $('#inscription_cgv');
        var utm_source = '<?php echo $source; ?>';

        if($('#form_inscription').hasClass('etape1')) {

            var erreur = 0;

            if(!inscription_civilite) {
                $('#inscription_civilite').next('.c2-sb-wrap').addClass('error');
                var erreur = 1;
            }
            if(!inscription_nom) {
                $('#inscription_nom').addClass('error');
                var erreur = 1;
            }
            if(!inscription_prenom) {
                $('#inscription_prenom').addClass('error');
                var erreur = 1;
            }
            if(!inscription_email) {
                $('#inscription_email').addClass('error');
                var erreur = 1;
            }
            if (!validateEmail(inscription_email)) {
                $('#inscription_email').addClass('error');
                var erreur = 1;
            }
            if (erreur == 1) { return false; }
            else {

                // AJAX

                var key = 'unilend';
                var hash = CryptoJS.MD5(key);
                var time = $.now();

                // var token = '<?php echo $token; ?>';
                var token = $.base64.btoa(hash+'-'+time);
                var localdate = new Date();
                var mois = localdate.getMonth()+1;
                var jour = localdate.getDate();
                var heure = localdate.getHours();
                var minutes = localdate.getMinutes();
                var secondes = localdate.getSeconds();
                if(mois<10) { mois = '0'+mois; }
                if(jour<10) { jour = '0'+jour; }
                if(heure<10) { heure = '0'+heure; }
                if(minutes<10) { minutes = '0'+minutes; }
                if(secondes<10) { secondes = '0'+secondes; }
                                    
                var date = localdate.getFullYear() + '-' + mois + '-' + jour + ' ' + heure + ':' + minutes + ':' + secondes;
                email = inscription_email;
                nom = inscription_nom;
                prenom = inscription_prenom;
                civilite = inscription_civilite;

                var DATA = '&token=' + token + '&utm_source=' + utm_source + '&date=' + date + '&email=' + email + '&nom=' + nom + '&prenom=' + prenom + '&civilite=' + civilite;

                $.ajax({
                    type: "POST",
                    url: "http://unilend.demo2.equinoa.net/collect/prospect",
                    data: DATA,
                    success: function(data){
                        var parsedDate = jQuery.parseJSON(data);

                        if(parsedDate.reponse == 'OK') {
                            $('#form_inscription').removeClass('etape1');
                            $('#form_inscription').addClass('etape2');

                            $('html, body').animate({
                                scrollTop: 0
                            }, 1000, 'swing');

                            $('#form_header').fadeOut('fast', function() {
                                $('#form_header').html('<h1>Complêtez</h1><h2>Votre inscription</h2>');
                                $('#form_header').fadeIn();
                            });

                            $('#form_inscription > .form_content.etape1').fadeOut('fast', function() {
                                $('#form').css('position','relative');
                                $('#form > .wrapper').addClass('etape2');
                                $('#form_inscription > .form_content.etape2').fadeIn();
                            });
                        }
                        else {
                            var key = 'unilend';
                            var hash = CryptoJS.MD5(key);
                            var time = $.now();
                            var token = $.base64.btoa(hash+'-'+time);

                            $.each( parsedDate.reponse, function( index, value ){
                                var intituleErreur = value.erreur;

                                if(intituleErreur == "Nom") {
                                    $('#inscription_nom').addClass('error');
                                }
                                if(intituleErreur == "Prenom") {
                                    $('#inscription_prenom').addClass('error');
                                }
                                if(intituleErreur == "Email" || intituleErreur == "Format email") {
                                    $('#inscription_email').addClass('error');
                                }
                                if(intituleErreur == "Email existant" && parsedDate.reponse.length > 1) {
                                    $('#inscription_email').addClass('error');
                                }
                                else {
                                    $('#form_inscription').removeClass('etape1');
                                    $('#form_inscription').addClass('etape2');

                                    $('#form_header').fadeOut('fast', function() {
                                        $('#form_header').html('<h1>Complétez</h1><h2>Votre inscription</h2>');
                                        $('#form_header').fadeIn();
                                    });

                                    $('#form_inscription > .form_content.etape1').fadeOut('fast', function() {
                                        $('#form').css('position','relative');
                                        $('#form > .wrapper').addClass('etape2');
                                        $('#form_inscription > .form_content.etape2').fadeIn();
                                    });
                                }
                            });
                        }
                    }
                });
                return false;
            }
        }
        else if($('#form_inscription').hasClass('etape2')) {

            var idSubmit = $("button[type=submit].clicked").attr("id");

            var erreur = 0;

            var localdate = new Date();
            var annee = localdate.getFullYear();
            var mois = localdate.getMonth()+1;
            var jour = localdate.getDate();
            var heure = localdate.getHours();
            var minutes = localdate.getMinutes();
            var secondes = localdate.getSeconds();

            if(mois<10) { mois = '0'+mois; }
            if(jour<10) { jour = '0'+jour; }
            if(heure<10) { heure = '0'+heure; }
            if(minutes<10) { minutes = '0'+minutes; }
            if(secondes<10) { secondes = '0'+secondes; }

            if(!inscription_mdp) {
                $('#inscription_mdp').addClass('error');
                var erreur = 1;
            }
            if(inscription_mdp.length < 6) {
                $('#inscription_mdp').addClass('error');
                var erreur = 1;
            }
            if(inscription_mdp.replace(/[^A-Z]/g, "").length == 0) {
                $('#inscription_mdp').addClass('error');
                var erreur = 1;
            }
            if(!inscription_mdp2) {
                $('#inscription_mdp2').addClass('error');
                var erreur = 1;
            }
            if(inscription_mdp2 != inscription_mdp) {
                $('#inscription_mdp2').addClass('error');
                var erreur = 1;
            }
            if(!inscription_adresse_fiscale) {
                $('#inscription_adresse_fiscale').addClass('error');
                var erreur = 1;
            }
            if(!inscription_ville_fiscale) {
                $('#inscription_ville_fiscale').addClass('error');
                var erreur = 1;
            }
            if(!inscription_cp_fiscale) {
                $('#inscription_cp_fiscale').addClass('error');
                var erreur = 1;
            }
            if(!$.isNumeric(inscription_cp_fiscale)) {
                $('#inscription_cp_fiscale').addClass('error');
                var erreur = 1;
            }
            if(!inscription_id_pays_fiscale) {
                $('#inscription_id_pays_fiscale').next('.c2-sb-wrap').addClass('error');
                var erreur = 1;
            }
            if(!inscription_adresse_correspondance && !inscription_ville_correspondance && !inscription_cp_correspondance && !inscription_id_pays_correspondance) {
                inscription_adresse_correspondance = '';
                inscription_ville_correspondance = '';
                inscription_cp_correspondance = '';
                inscription_id_pays_correspondance = '';
            }
            else {
                if (!inscription_adresse_correspondance) {
                    $('#inscription_adresse_correspondance').addClass('error');
                    var erreur = 1;
                }
                if(!inscription_ville_correspondance) {
                    $('#inscription_ville_correspondance').addClass('error');
                    var erreur = 1;
                }
                if(!inscription_cp_correspondance) {
                    $('#inscription_cp_correspondance').addClass('error');
                    var erreur = 1;
                }
                if(!$.isNumeric(inscription_cp_correspondance)) {
                    $('#inscription_cp_correspondance').addClass('error');
                    var erreur = 1;
                }
                if(!inscription_id_pays_correspondance) {
                    $('#inscription_id_pays_correspondance').next('.c2-sb-wrap').addClass('error');
                    var erreur = 1;
                }
            }
            if(!inscription_telephone) {
                $('#inscription_telephone').addClass('error');
                var erreur = 1;
            }
            if(inscription_telephone.length != 10 || !$.isNumeric(inscription_telephone)) {
                $('#inscription_telephone').addClass('error');
                var erreur = 1;
            }
            if(!inscription_id_nationalite) {
                $('#inscription_id_nationalite').next('.c2-sb-wrap').addClass('error');
                var erreur = 1;
            }
            var verif_date = 0;
            if(!inscription_date_naissance) {
                $('#inscription_date_naissance').addClass('error');
                var erreur = 1;
                var verif_date = 1;
            }
            if (!validateDate(inscription_date_naissance)) {
                $('#inscription_date_naissance').addClass('error');
                if(verif_date == 0) { $('#errorAge').html('La date doit être au format jj/mm/aaaa'); }
                var erreur = 1;
                var verif_date = 1;
            }
            var date_naissance = inscription_date_naissance;
            var split_date = date_naissance.split('/');

            if(split_date[2] > annee) {
                $('#inscription_date_naissance').addClass('error');
                if(verif_date == 0) { $('#errorAge').html('Année invalide'); }
                var erreur = 1;
                var verif_date = 1;
            }
            if(split_date[1] > 12) {
                $('#inscription_date_naissance').addClass('error');
                if(verif_date == 0) { $('#errorAge').html('Mois invalide'); }
                var erreur = 1;
                var verif_date = 1;
            }
            if(split_date[0] > 31) {
                $('#inscription_date_naissance').addClass('error');
                if(verif_date == 0) { $('#errorAge').html('Jours invalide'); }
                var erreur = 1;
                var verif_date = 1;
            }

            var majeur = 0;

            if(split_date[2] < (annee-18)) { majeur = 1; }
            else if(split_date[2] == (annee-18)) {
                if(split_date[1] < mois) { majeur = 1; }
                else if(split_date[1] == mois) {
                    if(split_date[0] <= jour) { majeur = 1; }
                    else { majeur = 0; }
                }
                else { majeur = 0; }
            }
            else { majeur = 0; }

            if(majeur == 0 && verif_date == 0) {
                $('#inscription_date_naissance').addClass('error');
                $('#errorAge').html('Vous devez être majeur');
                var erreur = 1;
            }
            if(!inscription_commune_naissance) {
                $('#inscription_commune_naissance').addClass('error');
                var erreur = 1;
            }
            if(!inscription_id_pays_naissance) {
                $('#inscription_id_pays_naissance').next('.c2-sb-wrap').addClass('error');
                var erreur = 1;
            }
            if(!inscription_cgv.is(":checked")) {
                $('#inscription_cgv').parent().find('label').addClass('error');
                var erreur = 1;
            }
            if(erreur == 1) { return false; }
            else {

                // AJAX

                var key = 'unilend';
                var hash = CryptoJS.MD5(key);
                var time = $.now();
                var token = $.base64.btoa(hash+'-'+time);
                                    
                var date = annee + '-' + mois + '-' + jour + ' ' + heure + ':' + minutes + ':' + secondes;
                email = inscription_email;
                nom = inscription_nom;
                prenom = inscription_prenom;
                civilite = inscription_civilite;
                var password = CryptoJS.MD5(inscription_mdp);
                var question = inscription_question;
                var reponse = inscription_reponse;
                var adresse_fiscale = inscription_adresse_fiscale;
                var ville_fiscale = inscription_ville_fiscale;
                var cp_fiscale = inscription_cp_fiscale;
                var id_pays_fiscale = inscription_id_pays_fiscale;
                var adresse = inscription_adresse_correspondance;
                var ville = inscription_ville_correspondance;
                var cp = inscription_cp_correspondance;
                var id_pays = inscription_id_pays_correspondance;
                var telephone = inscription_telephone;
                var id_nationalite = inscription_id_nationalite;
                var new_date_naissance = split_date[2] + '-' + split_date[1] + '-' + split_date[0];
                var commune_naissance = inscription_commune_naissance;
                var id_pays_naissance = inscription_id_pays_naissance;
                var signature_cgv = 1;
                var forme_preteur = 1;

                var DATA = '&token=' + token + '&utm_source=' + utm_source + '&date=' + date + '&email=' + email + '&nom=' + nom + '&prenom=' + prenom + '&civilite=' + civilite + '&password=' + password + '&question=' + question + '&reponse=' + reponse + '&adresse_fiscale=' + adresse_fiscale + '&ville_fiscale=' + ville_fiscale + '&cp_fiscale=' + cp_fiscale + '&id_pays_fiscale=' + id_pays_fiscale + '&adresse=' + adresse + '&ville=' + ville + '&cp=' + cp + '&id_pays=' + id_pays + '&telephone=' + telephone + '&id_nationalite=' + id_nationalite + '&date_naissance=' + new_date_naissance + '&commune_naissance=' + commune_naissance + '&id_pays_naissance=' + id_pays_naissance + '&signature_cgv=' + signature_cgv + '&forme_preteur=' + forme_preteur;

                $.ajax({
                    type: "POST",
                    url: "http://unilend.demo2.equinoa.net/collect/inscription",
                    data: DATA,
                    success: function(data){
                        var parsedDate = jQuery.parseJSON(data);

                        console.log(parsedDate);

                        if(parsedDate.reponse == 'OK') {
                            var url = parsedDate.URL;

                            if(idSubmit == "inscription_submit2") { $(location).attr('href', url); }
                            else if(idSubmit == "voir_projets") { $(location).attr('href', 'http://unilend.demo2.equinoa.net/projets-a-financer'); }
                        }
                        else {
                            var key = 'unilend';
                            var hash = CryptoJS.MD5(key);
                            var time = $.now();
                            var token = $.base64.btoa(hash+'-'+time);

                            $.each( parsedDate.reponse, function( index, value ){
                                var intituleErreur = value.erreur;

                                console.log(intituleErreur);

                                if(intituleErreur == "Mot de passe") {
                                    $('#inscription_mdp').addClass('error');
                                }
                                if(intituleErreur == "Question secrète") {
                                    $('#inscription_question').addClass('error');
                                }
                                if(intituleErreur == "Reponse secrète") {
                                    $('#inscription_reponse').addClass('error');
                                }
                                if(intituleErreur == "Adresse fiscale") {
                                    $('#inscription_adresse_fiscale').addClass('error');
                                }
                                if(intituleErreur == "Ville fiscale") {
                                    $('#inscription_ville_fiscale').addClass('error');
                                }
                                if(intituleErreur == "Code postal fiscale") {
                                    $('#inscription_cp_fiscale').addClass('error');
                                }
                                if(intituleErreur == "Pays fiscale") {
                                    $('#inscription_id_pays_fiscale').next('.c2-sb-wrap').addClass('error');
                                }
                                if(intituleErreur == "Adresse") {
                                    $('#inscription_adresse_correspondance').addClass('error');
                                }
                                if(intituleErreur == "Ville") {
                                    $('#inscription_ville_correspondance').addClass('error');
                                }
                                if(intituleErreur == "Code postal") {
                                    $('#inscription_cp_correspondance').addClass('error');
                                }
                                if(intituleErreur == "Pays") {
                                    $('#inscription_id_pays_correspondance').next('.c2-sb-wrap').addClass('error');
                                }
                                if(intituleErreur == "Téléphone") {
                                    $('#inscription_telephone').addClass('error');
                                }
                                if(intituleErreur == "Nationalité") {
                                    $('#inscription_id_nationalite').next('.c2-sb-wrap').addClass('error');
                                }
                                if(intituleErreur == "Date de naissance") {
                                    $('#inscription_date_naissance').addClass('error');
                                }
                                if(intituleErreur == "Commune de naissance") {
                                    $('#inscription_commune_naissance').addClass('error');
                                }
                                if(intituleErreur == "Pays de naissance") {
                                    $('#inscription_id_pays_naissance').next('.c2-sb-wrap').addClass('error');
                                }
                                if(intituleErreur == "Signature cgv") {
                                    $('#inscription_cgv').parent().find('label').addClass('error');
                                }
                            });
                        }
                    }
                });

                return false;
            }
        }
        else {
            return false;
        }


    });

    function validateEmail(emailAddress) {
        var emailRegex = new RegExp(/^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/);
        var valid = emailRegex.test(emailAddress);
        if (!valid) {
            return false;
        } else
            return true;
    }

    function validateDate(date) {
        var dateRegex = new RegExp(/^([0-9]{2}[\/][0-9]{2}[\/][0-9]{4})$/);
        var valid = dateRegex.test(date);
        if (!valid) {
            return false;
        } else
            return true;
    }
});