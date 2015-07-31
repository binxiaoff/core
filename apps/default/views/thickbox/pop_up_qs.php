<div class="popup">
	<a href="#" class="popup-close">close</a>
	
	<div class="popup-head">
		<h2>
        	<span class="valider_pret"><?=$this->lng['preteur-synthese']['pop-up-qs-title']?></span>
        </h2>
	</div>
	
	<div class="popup-cnt" >
    	<?
        if(isset($_SESSION['qs_ok']) && $_SESSION['qs_ok'] == 'OK'){
			unset($_SESSION['qs_ok']);
			?>
            <div class="row" style="text-align:center;color:green;">
           		<?=$this->lng['preteur-synthese']['pop-up-qs-question-et-reponse-secrete-enregistre']?>
            </div>
            <script type="text/javascript">
				setTimeout(function() {
					  $('.popup-close').click();
				}, 3000);
			</script>
            <?
		}
		else{
			?>
			<form action="" method="post" id="form_qs" name="form_qs">
				
				<div class="row" style="text-align:center;">
                <?=$this->lng['preteur-synthese']['pop-up-qs-vous-navez-pas-de-question-et-reponse-secrete']?>
				
				</div>
				
				<div class="row" style="text-align:center;">
					<input autocomplete="off" type="text" id="secret-question" name="secret-question" title="<?=$this->lng['etape1']['question-secrete']?>" value="<?=$this->lng['etape1']['question-secrete']?>" class="field field-large required" data-validators="Presence">
				</div><!-- /.row -->
	
				<div class="row" style="text-align:center;">
					<input autocomplete="off" type="text" id="secret-response" name="secret-response" title="<?=$this->lng['etape1']['response']?>" value="<?=$this->lng['etape1']['response']?>" class="field field-large required" data-validators="Presence">
				</div><!-- /.row -->
				
			   <div class="row" style="text-align:center;">
					<input type="hidden" name="send_form_qs" id="send_form_qs">
					<button class="btn" type="submit"><?=$this->lng['etape1']['valider']?><i class="icon-arrow-next"></i></button>
				</div>
			</form>
			<?
		}
		?>
    </div>
</div>
<script type="text/javascript">
	// Submit formulaire inscription preteur societe
	$( "#form_qs" ).submit(function( event ) {	
		var form_ok = true;
		// question
		if($('#secret-question').val() == '' || $('#secret-question').val() == $('#secret-question').attr('title')){
			$('#secret-question').addClass('LV_invalid_field');$('#secret-question').removeClass('LV_valid_field');form_ok = false;
		}
		else{$('#secret-question').addClass('LV_valid_field');$('#secret-question').removeClass('LV_invalid_field');}
		// reponse
		if($('#secret-response').val() == '' || $('#secret-response').val() == $('#secret-response').attr('title')){
			$('#secret-response').addClass('LV_invalid_field');$('#secret-response').removeClass('LV_valid_field');form_ok = false;
		}
		else{$('#secret-response').addClass('LV_valid_field');$('#secret-response').removeClass('LV_invalid_field');}
		
		if(form_ok == false){ event.preventDefault(); }
	
	});
</script>