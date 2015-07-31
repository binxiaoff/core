<div class="popup">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2><?=$this->lng['etape2']['documents-a-fournir']?></h2>
	</div>

	<div class="popup-cnt">
    	
        <p><?=$this->lng['etape2']['formats-autorises']?></p>
    
		<div class="rules-list">
			<ul class="unstyled-list">
				<li data-rule="1"><span>- <?=$this->lng['etape2']['extrait-kbis']?><i class="check-ico"></i></span></li>
				<li data-rule="2"><span>- <?=($this->companies->status_client == 2?$this->lng['etape2']['delegation-de-pouvoir-obligatoire']:$this->lng['etape2']['delegation-de-pouvoir'])?><i class="check-ico"></i></span></li>
				<li data-rule="3"><span>- <?=$this->lng['etape2']['rib']?><i class="check-ico"></i></span></li>
				<?php /*?><li data-rule="4"><span>- <?=$this->lng['etape2']['statuts']?><i class="check-ico"></i></span></li><?php */?>
				<li data-rule="5"><span>- <?=$this->lng['etape2']['cni-passeport-dirigeants']?><i class="check-ico"></i></span></li>
			</ul>
		</div>

		<div class="rules-form">
			<form action="<?=$this->lurl?>/inscription_preteur/etape2/<?=$this->params[0]?>" method="post" enctype="multipart/form-data" >
				<div class="select-holder clearfix">
					<select class="custom-select" id="rule-selector">
						<option value="0"><?=$this->lng['etape2']['nom']?></option>
						<option value="1"><?=$this->lng['etape2']['extrait-kbis']?></option>
						<option value="2"><?=($this->companies->status_client == 2?$this->lng['etape2']['delegation-de-pouvoir-obligatoire']:$this->lng['etape2']['delegation-de-pouvoir'])?></option>
						<option value="3"><?=$this->lng['etape2']['rib']?></option>
						<?php /*?><option value="4"><?=$this->lng['etape2']['statuts']?></option><?php */?>
						<option value="5"><?=$this->lng['etape2']['cni-passeport-dirigeants']?></option>
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
									<input type="file" class="file-field" name="fichier2"/>
								</span>
							</span>
						</div>
					</div>

					<div class="uploader" data-file="3">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape2']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier3"/>
								</span>
							</span>
						</div>
					</div>
                    
                   <?php /*?> <div class="uploader" data-file="4">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape2']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier4"/>
								</span>
							</span>
						</div>
					</div><?php */?>
                    
                    <div class="uploader" data-file="5">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape2']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier5"/>
								</span>
							</span>
						</div>
					</div>
                    
				</div>

				<div class="form-actions">
					<input type="hidden" name="form_pop_up_etape2" id="form_pop_up_etape2" />
					<button style="visibility:hidden;" id="valider" type="submit" class="btn"><?=$this->lng['etape2']['valider']?></button>
                    
                    
                    <div class="fichier1" style="display:none;"></div>
                    <div class="fichier3" style="display:none;"></div>
                    
                    <div class="fichier5" style="display:none;"></div>
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
		if($('.fichier1').html() != '' && $('.fichier3').html() != '' && $('.fichier4').html() != '' && $('.fichier5').html() != '')
		{
			$('#valider').css("visibility","visible")
		}
		
		
	}
})
</script>