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
    
    <?php /*?><script>
    
    
    
    
    $("#passNew2").change(function() {
        
        if($("#passNew2").val() != $("#passNew").val()){$(this).addClass('LV_invalid_field');$(this).removeClass('LV_valid_field');}
        else{$(this).addClass('LV_valid_field');$(this).removeClass('LV_invalid_field');}
        
    });
    
    $("#sub_form_mdp").click(function() {
        
        var oldMdp = $("#passOld").val();
        var newMdp = $("#passNew").val();
        var newMdp2 = $("#passNew2").val();
        var question = $("#secret-question").val();
        var reponse = $("#secret-response").val();
        
        var form_ok = true;
        
        if(oldMdp == ''){ form_ok = false;$("#passOld").addClass('LV_invalid_field');$("#passOld").removeClass('LV_valid_field');}
        else{$("#passOld").addClass('LV_valid_field');$("#passOld").removeClass('LV_invalid_field');}
        
        if(newMdp == ''){ form_ok = false;$("#passNew").addClass('LV_invalid_field');$("#passNew").removeClass('LV_valid_field');}
        else{$("#passNew").addClass('LV_valid_field');$("#passNew").removeClass('LV_invalid_field');}
        
        if(newMdp2 == ''){ form_ok = false;$("#passNew2").addClass('LV_invalid_field');$("#passNew2").removeClass('LV_valid_field');}
        else if(newMdp2 != newMdp){ form_ok = false;$("#passNew2").addClass('LV_invalid_field');$("#passNew2").removeClass('LV_valid_field');}
        else{$(this).addClass('LV_valid_field');$("#passNew2").removeClass('LV_invalid_field');}
        
        if(question != '<?=$this->lng['etape1']['question-secrete']?>' || reponse != '<?=$this->lng['etape1']['response']?>')
        {
            
            if(question == '<?=$this->lng['etape1']['question-secrete']?>'){  form_ok = false;$("#secret-question").addClass('LV_invalid_field');$("#secret-question").removeClass('LV_valid_field'); }
            else{ $("#secret-question").addClass('LV_valid_field');$("#secret-question").removeClass('LV_invalid_field'); }
            
            if(reponse == '<?=$this->lng['etape1']['response']?>'){ form_ok = false;$("#secret-response").addClass('LV_invalid_field');$("#secret-response").removeClass('LV_valid_field'); }
            else{ $("#secret-response").addClass('LV_valid_field');$("#secret-response").removeClass('LV_invalid_field'); }
        }
        else
        {
            $("#secret-question").removeClass('LV_valid_field');
            $("#secret-question").removeClass('LV_invalid_field');
            
            $("#secret-response").removeClass('LV_valid_field');
            $("#secret-response").removeClass('LV_invalid_field');
        }
        
        if(form_ok == true)
        {
            if(question == '<?=$this->lng['etape1']['question-secrete']?>'){question = '';}
            if(reponse == '<?=$this->lng['etape1']['response']?>'){reponse = '';}
            
            
            $.post(add_url + '/ajax/changeMdp', {oldMdp: oldMdp,newMdp: newMdp, id : <?=$this->clients->id_client?>, question : question,reponse : reponse}).done(function(data) {
    
                if(data == 'ok')
                {
                    $("#reponse_mdp").html('Votre mot de passe a bien été changé.');
                    $("#reponse_mdp").css('color','green');
                    $("#reponse_mdp").slideDown('slow');
                    setTimeout(function() {
                        $("#reponse_mdp").slideUp('slow');
                    }, 5000);
                    
                }
                else
                {
                    $("#passOld").addClass('LV_invalid_field');$("#passOld").removeClass('LV_valid_field');
                    $("#reponse_mdp").html('Votre ancien mot de passe n\'est pas valide');
                    $("#reponse_mdp").css('color','#C84747');
                    $("#reponse_mdp").slideDown('slow');
                    setTimeout(function() {
                        $("#reponse_mdp").slideUp('slow');
                    }, 5000);
                }
            });
        }
    });
    
    </script><?php */?>
</div>