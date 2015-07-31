<div class="popup">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2><?=$this->lng['etape2']['documents-a-fournir']?></h2>
	</div>

	<div class="popup-cnt">
    	
        <p><?=$this->lng['etape2']['formats-autorises']?></p>
    
		<div class="rules-list">
			<ul class="unstyled-list">
				<li data-rule="1"><span>- <?=$this->lng['etape2']['carte-nationale-didentite-passeport']?><i class="check-ico"></i></span></li>
				<li data-rule="2"><span>- <?=$this->lng['etape2']['justificatif-de-domicile']?><i class="check-ico"></i></span></li>
				<li data-rule="3"><span>- <?=$this->lng['etape2']['rib']?><i class="check-ico"></i></span></li>
			</ul>
		</div>

		<div class="rules-form">
			<form action="<?=$this->lurl?>/profile/2" method="post" enctype="multipart/form-data" >
				<div class="select-holder clearfix">
					<select class="custom-select" id="rule-selector">
						<option value="0"><?=$this->lng['etape2']['nom']?></option>
						<option value="1"><?=$this->lng['etape2']['carte-nationale-didentite-passeport']?></option>
						<option value="2"><?=$this->lng['etape2']['justificatif-de-domicile']?></option>
						<option value="3"><?=$this->lng['etape2']['rib']?></option>
					</select>
				</div>

				<div class="file-uploaders">
					<div class="uploader" data-file="1">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape2']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier1" />
								</span>
							</span>
						</div>
					</div>

					<div class="uploader" data-file="2">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape2']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier2" />
								</span>
							</span>
						</div>
					</div>

					<div class="uploader" data-file="3">
						<input type="text" class="field" readonly="readonly"/>
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape2']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier3"/>
								</span>
							</span>
						</div>
					</div>
				</div>

				<div class="form-actions">
                	<input type="hidden" name="form_pop_up_etape2" id="form_pop_up_etape2" />
					<button style="visibility:hidden;" id="valider" type="submit" class="btn"><?=$this->lng['etape2']['valider']?></button>
                    <div class="fichier1" style="display:none;"></div>
                    <div class="fichier2" style="display:none;"></div>
                    <div class="fichier3" style="display:none;"></div>
				</div>
			</form>
		</div>
	</div>
	<!-- /popup-cnt -->

</div>

<script>
$('input.file-field').on('change', function(){
	var $self = $(this),
		val = $self.val()

	if( val.length != 0 || val != '' ){
		//$self.closest('.uploader').find('input.field').val(val);
		var idx = $('#rule-selector').val();

		$('.fichier'+idx).html('1');
		if($('.fichier1').html() != '' && $('.fichier2').html() != '' && $('.fichier3').html() != '')
		{
			$('#valider').css("visibility","visible")
		}
		
		
	}
})
</script>