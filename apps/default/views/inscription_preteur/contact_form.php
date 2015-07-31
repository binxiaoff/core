<style type="text/css">
.popup-contact .btn {
    height: 47px;
}
.popup .form-actions{padding-top:0px;}
</style>
<div class="popup popup-contact">
	<a href="#" class="popup-close">Close</a>

	<div class="popup-head">
		<h2><?=$this->tree->getTitle(47,$this->language)?></h2>
	</div><!-- /.popup-head -->

	<div class="popup-cnt">
		<form action="" method="post" id="form_contact">
        	<div class="confirmation" style="color:green;display:none"><?=$this->lng['contact']['confirmation']?></div>
			<p><?=$this->lng['contact']['box-form-contact-contenu']?></p>

			<div class="row">
				<input type="text" class="field required" value="<?=($this->clients->nom!=''?$this->clients->nom:$this->lng['contact']['nom'])?>" title="<?=$this->lng['contact']['nom']?>" name="contact-form-name" id="contact-form-name" data-validators="Presence" onkeyup="noNumber(this.value,this.id);"/>
				<input type="text" class="field required" value="<?=($this->clients->prenom!=''?$this->clients->prenom:$this->lng['contact']['prenom'])?>" title="<?=$this->lng['contact']['prenom']?>" name="contact-form-prenom" id="contact-form-prenom" data-validators="Presence" onkeyup="noNumber(this.value,this.id);"/>
			</div><!-- /.row -->

			<div class="row">
				<input type="text" class="field required" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['contact']['email'])?>" title="<?=$this->lng['contact']['email']?>" name="contact-form-email" id="contact-form-email" data-validators="Presence&amp;Email"/>
				<input type="text" class="field" value="<?=($this->clients->telephone!=''?$this->clients->telephone:$this->lng['contact']['telephone'])?>" title="<?=$this->lng['contact']['telephone']?>" name="contact-form-phone" id="contact-form-phone" />
			</div><!-- /.row -->

			<div class="row">
				<input type="text" class="field" value="<?=$this->lng['contact']['societe']?>" title="<?=$this->lng['contact']['societe']?>" name="contact-form-societe" id="contact-form-societe" />
			</div><!-- /.row -->

			<div class="row">
				<textarea name="contact-form-message" id="contact-form-message" cols="30" rows="10" title="<?=$this->lng['contact']['message']?>" class="field"><?=$this->lng['contact']['message']?></textarea>
			</div><!-- /.row -->

			<div class="row row-security">
				<span class="security-code">
                <iframe width="133" src="<?=$this->surl?>/images/default/securitecode.php"></iframe> 
                </span>

				<input type="text" class="field required" value="<?=$this->lng['contact']['captcha']?>" title="<?=$this->lng['contact']['captcha']?>" name="contact-form-security" id="contact-form-security" data-validators="Presence" />
			</div><!-- /.row -->

			<div class="form-actions">
				<button class="btn" type="submit"><?=$this->lng['contact']['envoyer']?> <i class="icon-arrow-next"></i></button>
			</div><!-- /.form-actions -->
		</form>
       <script>
		$( "#form_contact" ).submit(function( event ) {
			event.preventDefault();
			
			var name = $('#contact-form-name').val();
			var prenom = $('#contact-form-prenom').val();
			var email = $('#contact-form-email').val();
			var phone = $('#contact-form-phone').val();
			var societe = $('#contact-form-societe').val();
			var message = $('#contact-form-message').val();
			var security = $('#contact-form-security').val();
			
			var form_ok = true;
			
			// name
			if(name == '' || name == $('#contact-form-name').attr( "title" )){form_ok = false;$("#contact-form-name").removeClass("LV_valid_field");$("#contact-form-name").addClass("LV_invalid_field");}
			else{$("#contact-form-name").removeClass("LV_invalid_field");$("#contact-form-name").addClass("LV_valid_field");}
			// prenom
			if(prenom == '' || prenom == $('#contact-form-prenom').attr( "title" )){form_ok = false;$("#contact-form-prenom").removeClass("LV_valid_field");$("#contact-form-prenom").addClass("LV_invalid_field");}
			else{$("#contact-form-prenom").removeClass("LV_invalid_field");$("#contact-form-prenom").addClass("LV_valid_field");}
			// email
			if(checkEmail(email) == false){form_ok = false;$("#contact-form-email").removeClass("LV_valid_field");$("#contact-form-email").addClass("LV_invalid_field");}
			else{$("#contact-form-email").removeClass("LV_invalid_field");$("#contact-form-email").addClass("LV_valid_field");}
			// message
			if(message == '' || message == $('#contact-form-message').attr( "title" )){form_ok = false;$("#contact-form-message").removeClass("LV_valid_field");$("#contact-form-message").addClass("LV_invalid_field");}
			else{$("#contact-form-message").removeClass("LV_invalid_field");$("#contact-form-message").addClass("LV_valid_field");}
			// security
			if(security == '' || security == $('#contact-form-security').attr( "title" )){
				form_ok = false;$("#contact-form-security").removeClass("LV_valid_field");$("#contact-form-security").addClass("LV_invalid_field");
			}
			else{
				
				$.post( add_url+"/ajax/captcha", { security: security }).done(function( data ) {
					if(data != 'nok'){
						$( ".security-code" ).html(data);
						form_ok = false;
						$("#contact-form-security").removeClass("LV_valid_field");$("#contact-form-security").addClass("LV_invalid_field");
					}
			   });
				
				$("#contact-form-security").removeClass("LV_invalid_field");$("#contact-form-security").addClass("LV_valid_field");
			}
			  
			// good 
			if(form_ok == true)
			{
				var val = {
				name:name,
				prenom:prenom,
				email:email,
				phone:phone,
				societe:societe,
				message	:message,
				security:security
				}
				
			   $.post( add_url+"/ajax/contact_form",val).done(function(data) {
					
					if(data != 'nok'){
						$( ".security-code" ).html(data);
						
						$(".confirmation").slideDown();
						
						$('#contact-form-name').val('');
						$('#contact-form-prenom').val('');
						$('#contact-form-email').val('');
						$('#contact-form-phone').val('');
						$('#contact-form-societe').val('');
						$('#contact-form-message').val('');
						$('#contact-form-security').val('');
						
						
						setTimeout(function() {
							$(".popup-close" ).click();
						}, 3000);
					}
			   });
			}
		   
		});
		</script> 
	</div><!-- /.popup-cnt -->
</div><!-- /.popup -->