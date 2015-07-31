<div class="popup">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2><?=$this->lng['emprunteur-projects']['titre-ulpoad-pouvoir']?></h2>
	</div>

	<div class="popup-cnt">
    
    	<p><?=$this->lng['emprunteur-projects']['formats-autorises']?></p>
    
		<div class="rules-list">
			<ul class="unstyled-list">
				<li data-rule="1"><span>- Pouvoir<i class="check-ico"></i></span></li>
			</ul>
		</div>

		<div class="rules-form">
			<form action="<?=$this->lurl?>/projects_emprunteur/detail/<?=$this->params[0]?>" method="post" enctype="multipart/form-data" >
				

				<div>
					<div class="uploader">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['emprunteur-projects']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="pouvoir" />
								</span>
							</span>
						</div>
					</div>
				</div>

				<div class="form-actions">
                	<input type="hidden" name="form_send_pouvoir" id="form_send_pouvoir" />
					<button type="submit" class="btn"><?=$this->lng['emprunteur-projects']['valider']?></button>
				</div>
			</form>
		</div>
	</div>
	<!-- /popup-cnt -->

</div>
