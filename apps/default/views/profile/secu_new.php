<div class="account-data">
    <h2><?=$this->lng['profile']['titre-3']?></h2>
    
    <?
	if(isset($_SESSION['reponse_profile_secu']) && $_SESSION['reponse_profile_secu'] != ''){
		?><div class="reponseProfile"><?=$_SESSION['reponse_profile_secu']?></div><br /><?
		unset($_SESSION['reponse_profile_secu']);
	}
	elseif(isset($_SESSION['reponse_profile_secu_error']) && $_SESSION['reponse_profile_secu_error'] != ''){
		?><div class="reponseProfile" style="color:#c84747;"><?=$_SESSION['reponse_profile_secu_error']?></div><br /><?
		unset($_SESSION['reponse_profile_secu_error']);
	}
	
	?>
    <form action="" method="post" id="form_mdp">
        <div class="row">
            <span class="pass-field-holder">
                <input type="password" name="passOld" id="passOld" title="<?=$this->lng['etape1']['ancien-mot-de-passe']?>" value="" class="field field-large required" data-validators="Presence">
            </span>
        </div><!-- /.row -->
        
        <div class="row">
            <span class="pass-field-holder">
                <input type="password" name="passNew" id="passNew" title="<?=$this->lng['etape1']['nouveau-mot-de-passe']?>" value="" class="field field-large required">
                <em style="margin-top:0px;"><?=$this->lng['etape1']['info-mdp']?></em>
            </span>
            
            <span class="pass-field-holder">
                <input type="password" name="passNew2" id="passNew2" title="<?=$this->lng['etape1']['confirmation-nouveau-mot-de-passe']?>" value="" class="field field-large" data-validators="Confirmation,{ match: 'passNew' }">
            </span>
        </div><!-- /.row -->
        
        <div class="row">
            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?=$this->lng['etape1']['info-question-secrete']?>"></i>
            <input type="text" id="secret-question" name="secret-question" title="<?=$this->lng['etape1']['question-secrete']?>" value="<?=$this->lng['etape1']['question-secrete']?>" class="field field-mega">
        </div><!-- /.row -->
    
        <div class="row">
            <input type="text" id="secret-response" name="secret-response" title="<?=$this->lng['etape1']['response']?>" value="<?=$this->lng['etape1']['response']?>" class="field field-mega">
        </div><!-- /.row -->
        
        <div class="form-foot row row-cols centered">
            <input type="hidden" name="send_form_mdp" id="send_form_mdp" value="">
            <button class="btn" id="sub_form_mdp" type="button" onClick='$( "#form_mdp" ).submit();' ><?=$this->lng['etape1']['valider-les-modifications']?><i class="icon-arrow-next"></i></button>
        </div><!-- /.form-foot foot-cols -->
    
    </form>
  
</div>

<script type="text/javascript">
	
	$(document).ready(function () {
		// Pass fields
		$('.pass-field-holder').each(function(){
			var $self = $(this),
				$input = $self.find('input'),
				$fake = $('<span class="fake-field">' + $input.attr('title') + '</span>');
			
			$self.append($fake);
			$fake.on('click', function(){ $fake.hide(); $input.trigger('focus'); });
	
			if($input[0].value.length){ $fake.hide(); }
		});
		
		// mdp controle particulier
		$('#passNew').keyup(function() { controleMdp($(this).val(),'passNew'); });
		// mdp controle particulier
		$('#passNew').blur(function() { controleMdp($(this).val(),'passNew'); });
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
	
</script>