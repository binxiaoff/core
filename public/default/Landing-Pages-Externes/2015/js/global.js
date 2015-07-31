/**********************************************************************/
var isMobile = {
    Android: function () {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function () {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function () {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);//
    },
    Opera: function () {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function () {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function () {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};
/**********************************************************************/
/*GESTION FORM*/
var ff;
$(window).load(function(){ 
    ff = $('#form_inscription').outerHeight();
	formactif();
	centerform();
});  
function formactif(){
	if ($( window ).width() < 960) {
		$('#form_header').addClass('formlight');
		
	} else {
		$('#form_header').removeClass('formlight');
		$('#form_header').removeClass('active');
		$('.form_content.etape1').css({"height":"auto"});
	}
}
function centerform(){
	if ($( window ).width() >= 960) { 
		var hh = $(window).height();
		var tt = (hh-ff)/2;
			
		$('#form').css({bottom:"auto"});
		$('#form_inscription.etape1').css({bottom:"auto", top:tt+"px"});
	} else {
		// $('#form').css({bottom:"0",top:"auto"});
		$('#form_inscription.etape1').css({bottom:"0", top:"auto"});
	}
}
/*****************************************************************************/
$(function(){

    $('#tooltip1, #tooltip2, #tooltip3').tooltip();

    $('input').placeholder();

    $('#form_inscription > .form_content.etape2').hide();

	/*GESTION FORM*/
	$( window ).resize(function() {
        formactif();
        centerform();

        if($('#form_header').hasClass('formlight')) {
            if($('#form_header').hasClass('active')) {
                if( $('#form_inscription').prop('scrollHeight') + $('.form_content').prop('scrollHeight') > $(window).height()) {
                    var formH = $(window).height() - $('#form_header').height() - $('.form_promo').height() - 40;
                    $('.form_content.etape1').css({ height: formH });
                }
            }
        }
    });
	

	$('#form_header').click(function() {		
		if($('#form_header').hasClass('formlight')) {
			if($('#form_header').hasClass('active')) {
                if( $('#form_inscription').prop('scrollHeight') + $('.form_content').prop('scrollHeight') < $(window).height()) {
                    $('.form_content.etape1').stop().animate({height:0});
                $('#form_header').removeClass('active');
                }
                else {
                    $('#form').stop().animate({ bottom:0 }, function() {
                        $('#form').css({ top:'auto' });
                    });
                    $('.form_content.etape1').stop().animate({height:0});
                    $('#form_inscription.etape1').css({ bottom:0 });
                    $('#form_header').removeClass('active');
                }
			}
			else {
                if( $('#form_inscription').prop('scrollHeight') + $('.form_content').prop('scrollHeight') < $(window).height()) {
                    var formH = $('.form_content').prop('scrollHeight');
                    $('.form_content.etape1').stop().animate({height: formH });
                    $('#form_header').addClass('active');
                }
                else {
                    var formH = $(window).height() - $('#form_header').height() - $('.form_promo').height() - 40;
                    $('#form_inscription.etape1').css({ bottom:'auto' });
                    $('#form').stop().animate({ top:0 });
                    $('.form_content.etape1').stop().animate({ height: formH }, function() {
                        $('#form_header').addClass('active');
                    });
                }
			}
		}
	});
	/************************/
    /**** slider projets ****/

    $("#slider_projet > div > div > div").width($("#slider_projet > div > div > div > div").size() * 180);

    $("#slide_suiv").stop().click(function() {
        $("#slider_projet > div > div > div").stop().animate({left: "-=180px"}, 300, function() {
            $("#slider_projet > div > div > div > div:last").after($("#slider_projet > div > div > div > div:first"));
            $("#slider_projet > div > div > div").css("left", "0");
        });
    });

    $("#slide_prec").stop().click(function() {
        $("#slider_projet > div > div > div > div:first").before($("#slider_projet > div > div > div > div:last"));
        $("#slider_projet > div > div > div").css("left", "-180px");
        $("#slider_projet > div > div > div").stop().animate({left: "+=180px"}, 300, function() {

            $("#slider_projet > div > div > div").css("left", "0");
        });
    });

    var swipeOptions = {
        triggerOnTouchEnd: true,
        swipeStatus: swipeStatus,
        allowPageScroll: "vertical",
        threshold: 0
    };

    $("#slider_projet > div > div").swipe(swipeOptions);


    /**** select duree ****/

    $('#dc_slider-step').noUiSlider({
        start: [ 60 ],
        connect: "lower",
        step: 12,
        range: {
            'min': [  24 ],
            'max': [ 60 ]
        },
        format: wNumb({
            decimals: 0
        })
    });

    /**** calcul interets ****/

    $("#simuler").click(function() {

        $("#erreur_simulation").html('');

        var str_capital = $("#pret_somme").val();
        if(!str_capital) {
            $("#erreur_simulation").html('Vous devez entrer une somme à prêter');
            return false;
        }

        var capital = str_capital.replace(" ", "");

        if(!$.isNumeric(capital)) {
            $("#erreur_simulation").html('Vous devez entrer une somme à prêter valide');
            return false;
        }
        if(capital<20) {
            $("#erreur_simulation").html('Le montant minimum de prêt est de 20€');
            return false;
        }
        if(capital>1000000) {
            $("#erreur_simulation").html('Le montant maximum de prêt est de 1 000 000€');
            return false;
        }
        
        var taux = $("#pret_taux").val();
        var duree = $("#dc_slider-step").val();

        var mensualitee = (capital*(taux/12))/(1-Math.pow(1+taux/12,-duree));
        var totalPercu = mensualitee*duree;
        var interetsBruts = totalPercu-capital;
        var tauxInteretsBruts = interetsBruts/capital*100;

        mensualitee = mensualitee.toFixed(2).replace(".",",");
        totalPercu = totalPercu.toFixed(2).replace(".",",");
        interetsBruts = interetsBruts.toFixed(2).replace(".",",");
        tauxInteretsBruts = tauxInteretsBruts.toFixed(2).replace(".",",");

        $('#recu_somme').html(totalPercu);
        $('#recu_right > p > span').html(mensualitee);
        $('#recu_right > p > span ~ span').html(duree);
        $('#recu_result > p > span > span').html(interetsBruts);
        $('#recu_result > p + p > span').html(tauxInteretsBruts);

        $('#recu').css({display: 'block'});
        $('#recu').stop().animate({height: $('#recu').prop('scrollHeight')});

        if (isMobile.any()) {
            $('html, body').animate({
                scrollTop: $('#recu').offset().top + 40
            }, 700, 'swing');
        };
    });

    /**** custon checkbox & select ****/

    $('.custom-select').c2Selectbox();

    $('#inscription_correspondance').hide();

    $('label[for=inscription_check_adresse]').click(function (event) {
        event.preventDefault();

        var $input = $('#inscription_check_adresse');
        var $parent = $input.parent();

        if($input.is('.custom-chekckbox')){
            if($input.is('[type="checkbox"]')){
                if(!$parent.is('.disabled')){
                    if($parent.is('.checked')){
                        $parent.removeClass('checked')
                        $input.prop('checked', false);
                        $('#inscription_correspondance').slideDown();
                    }else{
                        $parent.addClass('checked');
                        $input.prop('checked', true);
                        $('#inscription_correspondance').slideUp();
                    }
                }
            }
        }
    });

    $('label[for=inscription_cgv]').click(function (event) {
        event.preventDefault();

        var $input = $('#inscription_cgv');
        var $parent = $input.parent();

        if($input.is('.custom-chekckbox')){
            if($input.is('[type="checkbox"]')){
                if(!$parent.is('.disabled')){
                    if($parent.is('.checked')){
                        $parent.removeClass('checked')
                        $input.prop('checked', false);
                    }else{
                        $parent.addClass('checked');
                        $input.prop('checked', true);
                    }
                }
            }
        }
    });

    $('#inscription_utm_source3').change(function (event) {
        event.preventDefault();

        if ($(this).val() == 5) {
            $('#inscription_utm_source3_autre').show();
            // var input = document.createElement("INPUT");
            // input.type = "text";
            // input.name = "utm_source3_autre";
            // input.id = "inscription_utm_source3_autre";
            // input.placeholder = "Précisez...";
            // $(input).attr('maxlength','255');
            // $('#inscription_submit').before(input);
            // $('#inscription_submit').before('<input type="text" id="inscription_utm_source3_autre" name="utm_source3_autre" placeholder="Précisez..." maxlength="255" value="">');
        }
        else {
            $('#inscription_utm_source3_autre').val('');
            $('#inscription_utm_source3_autre').hide();
        }
    });

    /**** bouton scroll to top ****/

    $('#scrollUp').click(function (event) {
        event.preventDefault();
        $('html, body').animate({
            scrollTop: 0 /*- $('nav').height() * 0.5*/
        }, 1000, 'swing');
    }); 
});

function swipeStatus(event, phase, direction, distance) {

    //If we are moving before swipe, and we are going L or R in X mode, or U or D in Y mode then drag.
    if (phase == "move" && (direction == "left" || direction == "right")) {
        var duration = 0;

        if (direction == "left") {
            $("#slider_projet > div > div > div").stop().animate({left: "-=180px"}, 300, function() {
                $("#slider_projet > div > div > div > div:last").after($("#slider_projet > div > div > div > div:first"));
                $("#slider_projet > div > div > div").css("left", "0");
            });
        } else if (direction == "right") {
            $("#slider_projet > div > div > div > div:first").before($("#slider_projet > div > div > div > div:last"));
            $("#slider_projet > div > div > div").css("left", "-180px");
            $("#slider_projet > div > div > div").stop().animate({left: "+=180px"}, 300, function() {

                $("#slider_projet > div > div > div").css("left", "0");
            });
        }
        return false;
    }
}
