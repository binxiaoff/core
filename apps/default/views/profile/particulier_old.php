<style type="text/css">
	.tabs .tab{display:block;}
	.field-large {width: 422px;}
	.tab .form-choose{margin-bottom:0;}
	.form-page form .row .pass-field-holder {
    width: 460px;
}
</style>

<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main form-page account-page account-page-personal">
    <div class="shell">
        
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul class="navProfile">
                    
                    <li <?=(!isset($this->params[0])?'class="active"':'')?>>
                        <a id="perso" href="#"><?=$this->lng['profile']['titre-1']?></a>
                    </li>
                    
                    <li <?=(isset($this->params[0]) && $this->params[0] == 2?'class="active"':'')?> >
                        <a id="bank" href="#"><?=$this->lng['profile']['titre-2']?></a>
                    </li>
                    
                    <li <?=(isset($this->params[0]) && $this->params[0] == 3?'class="active"':'')?> >
                        <a id="secu" href="#"><?=$this->lng['profile']['titre-3']?></a>
                    </li>
                     
                </ul>
            </nav>

            <div class="tabs">

                <div class="tab page1">
                    <?=$this->fireView('particulier_perso')?>	
                </div><!-- /.tab -->

                <div class="tab page2">
                    <?=$this->fireView('particulier_bank')?>
                </div><!-- /.tab -->

                <div class="tab page3">
                    <?=$this->fireView('secu')?>
                </div>

                <?php /*?><div class="tab page4">
                    <?=$this->fireView('particulier_doc')?>
                </div><!-- /.tab --><?php */?>

            </div>

        </div><!-- /.tabs-c -->

    </div>
</div>		
<!--#include virtual="ssi-footer.shtml"  -->

<script type="text/javascript">
	$(window).load(function() {
		<?
		if(isset($this->params[0]) && $this->params[0] > 1 && $this->params[0] <= 3){
			for($i=1;$i<=3;$i++){ if($this->params[0] != $i){ ?>$(".page<?=$i?>" ).hide();<? }}
		}
		else{ ?> $(".page2" ).hide(); $(".page3" ).hide();<? }
		?>
		
	});
	
	$(document).ready(function () {
		// confirmation email preteur particulier
		$('#conf_email').bind('paste', function (e) { e.preventDefault(); });
		$('#email').bind('paste', function (e) { e.preventDefault(); });
		// mdp controle particulier
		$('#passNew').keyup(function() { controleMdp($(this).val(),'passNew'); });
		// mdp controle particulier
		$('#passNew').blur(function() { controleMdp($(this).val(),'passNew'); });
		
		////////////////////////////////////////////
		$( "#jour_naissance" ).change(function() {
			var d = $('#jour_naissance').val();
			var m = $('#mois_naissance').val();
			var y = $('#annee_naissance').val();
			
			$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
				if(data == 'ok'){
					//$("#jour_naissance").removeClass("LV_invalid_field");$("#jour_naissance").addClass("LV_valid_field");
					$(".check_age").html('true');
					$(".error_age").slideUp();
					
				}
				else{
					radio = false;//$("#jour_naissance").removeClass("LV_valid_field");$("#jour_naissance").addClass("LV_invalid_field");
					$(".check_age").html('false');
					$(".error_age").slideDown();
				}
			});
		});
		
		$( "#mois_naissance" ).change(function() {
		var d = $('#jour_naissance').val();
			var m = $('#mois_naissance').val();
			var y = $('#annee_naissance').val();
			
			$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
				if(data == 'ok'){
					//$("#jour_naissance").removeClass("LV_invalid_field");$("#jour_naissance").addClass("LV_valid_field");
					$(".check_age").html('true');
					$(".error_age").slideUp();
					
				}
				else{
					radio = false;//$("#jour_naissance").removeClass("LV_valid_field");$("#jour_naissance").addClass("LV_invalid_field");
					$(".check_age").html('false');
					$(".error_age").slideDown();
				}
			});
		});
		
		$( "#annee_naissance" ).change(function() {
		var d = $('#jour_naissance').val();
			var m = $('#mois_naissance').val();
			var y = $('#annee_naissance').val();
			
			$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
				if(data == 'ok'){
					//$("#jour_naissance").removeClass("LV_invalid_field");$("#jour_naissance").addClass("LV_valid_field");
					$(".check_age").html('true');
					$(".error_age").slideUp();
					
				}
				else{
					radio = false;//$("#jour_naissance").removeClass("LV_valid_field");$("#jour_naissance").addClass("LV_invalid_field");
					$(".check_age").html('false');
					$(".error_age").slideDown();
				}
			});
		});
	////////////////////////////////////////////////////
		
		// particulier etranger
		$("#pays1,#nationalite").change(function() {
			var pays1 = $('#pays1').val();
			var nationalite = $('#nationalite').val();
			
			//resident etranger
			if(nationalite > 0 && pays1 > 1){ 
				$(".etranger").slideDown();
				if(nationalite == 1 && pays1 > 1){ $(".etranger1").slideDown();$(".etranger2").slideUp(); }
				else if(nationalite != 1 && pays1 > 1){ $(".etranger2").slideDown();$(".etranger1").slideUp();}
			}
			else{ $(".etranger").slideUp();$(".etranger1").slideUp();$(".etranger2").slideUp(); }
		});
	
		// particulier messagge check_etranger
		$("#check_etranger").change(function() {
			if($(this).is(':checked') == true){
				$(".message_check_etranger").slideUp();
				$("#text_document_fiscal_1").val('');
				$("#text_document_fiscal_2").val('');
			}
			else{$(".message_check_etranger").slideDown();}
		});
				
	});

	
	// perso
	$("#form_particulier_perso").submit(function( event ) {
		var form_ok = true;
		var text_ci = $("#text_ci");
		var text_just_dom = $("#text_just_dom");
		
		if($(".check_age").html() == 'false'){
			form_ok = false;
		}
		
		
		// check cp //
		//checkCp('ville_inscription','postal');
		var id_ville = 'ville_inscription';
		var id_cp = 'postal';
		var cp = $('#'+id_cp).val();
		var ville =  $('#'+id_ville).val();
		
		var title_cp = $('#'+id_cp).attr("title");
		var title_ville = $('#'+id_ville).attr("title");
		
		if(title_ville == ville) ville = '';
		if(title_cp == cp) cp = '';
		
		$.post(add_url + '/ajax/checkCp', { ville: ville, cp: cp },function(data){ 
			if(data != 'ok'){ $('#'+id_cp).addClass('LV_invalid_field'); $('#'+id_cp).removeClass('LV_valid_field');form_ok = false; }
			else{ $('#'+id_cp).addClass('LV_valid_field'); $('#'+id_cp).removeClass('LV_invalid_field'); }
		});
		//fin check cp //
		
		if($('#mon-addresse').is(':checked') == false){
			
			//checkCp('ville_inscription','postal');
			var id_ville = 'ville2';
			var id_cp = 'postal2';
			var cp = $('#'+id_cp).val();
			var ville =  $('#'+id_ville).val();
			
			var title_cp = $('#'+id_cp).attr("title");
			var title_ville = $('#'+id_ville).attr("title");
			
			if(title_ville == ville) ville = '';
			if(title_cp == cp) cp = '';
			
			$.post(add_url + '/ajax/checkCp', { ville: ville, cp: cp },function(data){ 
				if(data != 'ok'){ $('#'+id_cp).addClass('LV_invalid_field'); $('#'+id_cp).removeClass('LV_valid_field');form_ok = false; }
				else{ $('#'+id_cp).addClass('LV_valid_field'); $('#'+id_cp).removeClass('LV_invalid_field'); }
			});
			//fin check cp //
		}
		
		
		//resident etranger
		var pays1 = $('#pays1').val();
		var nationalite = $('#nationalite').val();
		if(nationalite > 0 && pays1 > 1){
			// check_etranger
			if($('#check_etranger').is(':checked') == false){$('.check_etranger').css('color','#C84747'); $('#check_etranger').addClass('LV_invalid_field');$('#check_etranger').removeClass('LV_valid_field');  form_ok = false; }
			else{ $('#check_etranger').addClass('LV_valid_field');$('#check_etranger').removeClass('LV_invalid_field'); $('.check_etranger').css('color','#727272');}
			
			var text_document_fiscal = $("#text_document_fiscal_1");
			
			// document fiscal
			if(nationalite == 1 && pays1 > 1){var text_document_fiscal = $("#text_document_fiscal_1"); }
			else if(nationalite != 1 && pays1 > 1){var text_document_fiscal = $("#text_document_fiscal_2");}
				
			if(text_document_fiscal.val() == '' || text_document_fiscal.val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){form_ok = false; text_document_fiscal.addClass('LV_invalid_field');text_document_fiscal.removeClass('LV_valid_field');}
			else { text_document_fiscal.addClass('LV_valid_field');text_document_fiscal.removeClass('LV_invalid_field'); }
		}
		
		// ci
		if(text_ci.val() == ''){form_ok = false; text_ci.addClass('LV_invalid_field');text_ci.removeClass('LV_valid_field');}
		else { text_ci.addClass('LV_valid_field');text_ci.removeClass('LV_invalid_field'); }
		// just domicile
		if(text_just_dom.val() == ''){form_ok = false; text_just_dom.addClass('LV_invalid_field');text_just_dom.removeClass('LV_valid_field');}
		else { text_just_dom.addClass('LV_valid_field');text_just_dom.removeClass('LV_invalid_field'); }
		
		if(form_ok == false){event.preventDefault(); }
	});
	
	// secu
	
	$("#form_mdp").submit(function( event ) {
		
		var form_ok = true;
		var question = $("#secret-question");
		var reponse = $("#secret-response");
		var newpass = $('#passNew');
		
		// question secrete /reponse secrete
		if(question.val() != '' && question.val() != question.attr('title') || reponse.val() != '' && reponse.val() != reponse.attr('title')){
			// reponse vide
			if(reponse.val() == '' || reponse.val() == reponse.attr('title')){
				reponse.addClass('LV_invalid_field');reponse.removeClass('LV_valid_field');form_ok = false; }
			else { reponse.addClass('LV_valid_field');reponse.removeClass('LV_invalid_field'); }
			// question vide
			if(question.val() == '' || question.val() == question.attr('title')){ 
				question.addClass('LV_invalid_field');question.removeClass('LV_valid_field');form_ok = false;}
			else { question.addClass('LV_valid_field');question.removeClass('LV_invalid_field'); }
		}
		// controle mdp
		if(controleMdp(newpass.val(),'passNew') == false){form_ok = false}
		

		if(form_ok == false){event.preventDefault(); }
	});
	
	// info bank //

	// BIC
	$("#bic").keyup(function() { check_bic($(this).val()); });
	$("#bic").change(function() { check_bic($(this).val()); if($(this).val() != "<?=$this->lenders_accounts->bic?>"){$(".change_bank").fadeIn(); $('#txt_rib').val('');}});
	
	// IBAN
	for(var i=2;i<=7;i++){
		$('#iban-'+i).change(function() {
			$(".change_bank").fadeIn();
			$('#txt_rib').val('');
		});	
	}
	
	$('#iban-1').change(function() {
		if($("#iban-1").val().substring(0,2).toLowerCase() != 'fr'){	
			$("#iban-1").addClass('LV_invalid_field');$("#iban-1").removeClass('LV_valid_field');
			$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
			$(".error_iban").slideDown();
		}
		else{ 
			$("#iban-1").addClass('LV_valid_field');$("#iban-1").removeClass('LV_invalid_field');
			$(".error_iban").slideUp();
		}
	});
	
	
	// formulaire informations bancaires
	$("#form_bank").submit(function( event ) {
		var form_ok = true;
		
		// origine
		if($('#origine_des_fonds').val() == 0){
			$('#origine_des_fonds').addClass('LV_invalid_field');$('#origine_des_fonds').removeClass('LV_valid_field');
			form_ok = false;
		}
		else{$('#origine_des_fonds').addClass('LV_valid_field');$('#origine_des_fonds').removeClass('LV_invalid_field');}
		
		// rib 
		if($('#txt_rib').val() == ''){ form_ok = false; $('#txt_rib').addClass('LV_invalid_field');$('#txt_rib').removeClass('LV_valid_field');}
		else { $('#txt_rib').addClass('LV_valid_field');$('#txt_rib').removeClass('LV_invalid_field'); }
		
		// check BIC
		if(check_bic($("#bic").val()) == false){form_ok = false;}
	
		//check IBAN
		var iban_ok = true;
		var size_iban = true;
		var new_iban = '';
		for(var i=1;i<=7;i++){
			// 4 caracteres
			if(i < 7){
				if($('#iban-'+i).val().length < 4 || $('#iban-'+i).val().length > 4){
					check_ibanNB('iban-'+i,$('#iban-'+i).val(),4);
					size_iban = false
				}
				else new_iban = new_iban+$('#iban-'+i).val();
			}
			// 3 caracteres
			else{
				if($('#iban-'+i).val().length < 3 || $('#iban-'+i).val().length > 3){
					check_ibanNB('iban-'+i,$('#iban-'+i).val(),3);
					size_iban = false	
				}
				else new_iban = new_iban+$('#iban-'+i).val();
			}
		}
		
		if($("#iban-1").val().substring(0,2).toLowerCase() != 'fr'){	
			$("#iban-1").addClass('LV_invalid_field');$("#iban-1").removeClass('LV_valid_field');
			$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
			form_ok = false;
			iban_ok = false;	
		}
		else{ 
			$("#iban-1").addClass('LV_valid_field');$("#iban-1").removeClass('LV_invalid_field');
			$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur']?>');
		}
		
		// Lorsque l'on a le bon nombre de caracteres
		if(size_iban == true)
		{
			// On verifie si l'IBAN est bon
			if(validateIban(new_iban) == false){
				for(var i=1;i<=7;i++){
					$("#iban-"+i).addClass('LV_invalid_field');$("#iban-"+i).removeClass('LV_valid_field');
				}
				iban_ok = false;
			}
			else{
				for(var i=1;i<=7;i++){
					$("#iban-"+i).addClass('LV_valid_field');$("#iban-"+i).removeClass('LV_invalid_field');
				}
			}
		}
		else{
			iban_ok = false;
		}
		
		
		
		// Resultat check IBAN
		if(iban_ok == false){
			$(".error_iban").show();
			form_ok = false
		}
		else $(".error_iban").hide();

		if($('#origine_des_fonds').val() == '1000000'){
			if($('#preciser').val() == '' || $('#preciser').val() == $('#preciser').attr('title')){
				form_ok = false;
				$("#preciser").removeClass("LV_valid_field");$("#preciser").addClass("LV_invalid_field");
			}
			else{ $("#preciser").removeClass("LV_invalid_field");$("#preciser").addClass("LV_valid_field"); }
		}
		
		if(form_ok == false){
			event.preventDefault();
		}
	});
	
	
</script>
