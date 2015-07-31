<div class="popup" style="min-height: 700px;">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2><?=$this->lng['etape5']['documents-a-fournir']?></h2>
	</div>

	<div class="popup-cnt">
    	
        <p><?=$this->lng['etape5']['formats-autorises']?></p>
    
		<div class="rules-list">
			<ul class="unstyled-list" style="font-size: 11px;">
				<li data-rule="1"><span>- <?=$this->lng['etape5']['extrait-kbis']?><i class="check-ico"></i></span></li>
				<li data-rule="6"><span>- <?=$this->lng['etape5']['cni-passeport']?><i class="check-ico" style="right: -18px;"></i></span></li>
				
                <li data-rule="2"><span>- <?=$this->lng['etape5']['rib']?><i class="check-ico" style="right: -18px;"></i></span></li>
                
                <li data-rule="7"><span>- <?=$this->lng['etape5']['derniere-liasse-fiscale']?><i class="check-ico"></i></span></li>
                <li data-rule="8"><span>- <?=$this->lng['etape5']['derniers-comptes-approuves']?><i class="check-ico"></i></span></li>
                <li data-rule="9"><span>- <?=$this->lng['etape5']['derniers-comptes-consolides-groupe']?><i class="check-ico"></i></span></li>
                <li data-rule="10"><span>- <?=$this->lng['etape5']['annexes-rapport-special-commissaire-compte']?><i class="check-ico" style="right: -18px;">></i></span></li>
                
				<li data-rule="3"><span>- <?=($this->companies->status_client == 2?$this->lng['etape5']['delegation-de-pouvoir-obligatoire']:$this->lng['etape5']['delegation-de-pouvoir'])?><i class="check-ico"></i></span></li>
                <li data-rule="11"><span>- <?=$this->lng['etape5']['arret-comptable-recent']?><i class="check-ico"></i></span></li>
                <li data-rule="12"><span>- <?=$this->lng['etape5']['budget-exercice-en-cours-a-venir']?><i class="check-ico"></i></span></li>
                <li data-rule="13"><span>- <?=$this->lng['etape5']['notation-banque-france']?><i class="check-ico"></i></span></li>
                
                
				<li data-rule="4"><span>- <?=$this->lng['etape5']['logo-de-la-societe']?><i class="check-ico"></i></span></li>
				<li data-rule="5"><span>- <?=$this->lng['etape5']['photo-du-dirigeant']?><i class="check-ico"></i></span></li>
			</ul>
		</div>

		<div class="rules-form">
			<form action="<?=$this->lurl?>/depot_de_dossier/etape5/<?=$this->params[0]?>" method="post" enctype="multipart/form-data">
				<div class="select-holder clearfix">
					<select class="custom-select" id="rule-selector">
						<option value="0"><?=$this->lng['etape5']['nom']?></option>
						<option value="1"><?=$this->lng['etape5']['extrait-kbis']?></option>
                        <option value="6"><?=$this->lng['etape5']['cni-passeport']?></option>
						<option value="2"><?=$this->lng['etape5']['rib']?></option>
                        <option value="7"><?=$this->lng['etape5']['derniere-liasse-fiscale']?></option>
                        <option value="8"><?=$this->lng['etape5']['derniers-comptes-approuves']?></option>
                        <option value="9"><?=$this->lng['etape5']['derniers-comptes-consolides-groupe']?></option>
                        <option value="10"><?=$this->lng['etape5']['annexes-rapport-special-commissaire-compte']?></option>
						<option value="3"><?=($this->companies->status_client == 2?$this->lng['etape5']['delegation-de-pouvoir-obligatoire']:$this->lng['etape5']['delegation-de-pouvoir'])?></option>
                        <option value="11"><?=$this->lng['etape5']['arret-comptable-recent']?></option>
                        <option value="12"><?=$this->lng['etape5']['budget-exercice-en-cours-a-venir']?></option>
                        <option value="13"><?=$this->lng['etape5']['notation-banque-france']?></option>
						<option value="4"><?=$this->lng['etape5']['logo-de-la-societe']?></option>
						<option value="5"><?=$this->lng['etape5']['photo-du-dirigeant']?></option>
					</select>
				</div>

				<div class="file-uploaders">
					<div class="uploader" data-file="1">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
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
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier2" />
								</span>
							</span>
						</div>
					</div>

					<div class="uploader" data-file="3">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier3"/>
								</span>
							</span>
						</div>
					</div>

					<div class="uploader" data-file="4">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier4" />
								</span>
							</span>
						</div>
					</div>

					<div class="uploader" data-file="5">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier5"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="6">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier6"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="7">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier7"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="8">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier8"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="9">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier9"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="10">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier10"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="11">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier11"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="12">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier12"/>
								</span>
							</span>
						</div>
					</div>
                    <div class="uploader" data-file="13">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['etape5']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="fichier13"/>
								</span>
							</span>
						</div>
					</div>
				</div>

				<div class="form-actions">
                	<?php /*?>close-btn<?php */?>
                    <input type="hidden" name="send_form_upload">
					<button style="visibility:hidden;" id="valider" type="submit" class="btn"><?=$this->lng['etape5']['valider']?></button>
                   
                    <div class="fichier1" style="display:none;"></div>
                    <div class="fichier2" style="display:none;"></div>
                    <div class="fichier6" style="display:none;"></div>
                    <div class="fichier7" style="display:none;"></div>
                    <div class="fichier8" style="display:none;"></div>
                    
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
		if($('.fichier1').html() != '' && $('.fichier2').html() != '' && $('.fichier6').html() != '' && $('.fichier7').html() != '' && $('.fichier8').html() != '')
		{
			$('#valider').css("visibility","visible")
		}
		
		
	}
})
</script>

