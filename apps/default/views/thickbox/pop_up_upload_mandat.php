<div class="popup">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2><?=$this->lng['profile']['titre-ulpoad-mandat']?></h2>
	</div>

	<div class="popup-cnt">
    	
        <p><?=$this->lng['profile']['formats-autorises']?></p>
    
		<div class="rules-list">
			<ul class="unstyled-list">
				<li data-rule="1"><span>- Mandat<i class="check-ico"></i></span></li>
			</ul>
		</div>

		<div class="rules-form">
			<form action="<?=$this->urlRedirect?>" method="post" enctype="multipart/form-data" >
				

				<div>
					<div class="uploader">
						<input type="text" class="field" readonly="readonly" />
						<div class="file-holder">
							<span class="btn btn-small">
								<?=$this->lng['profile']['parcourir']?>
								<span class="file-upload">
									<input type="file" class="file-field" name="mandat" />
								</span>
							</span>
						</div>
					</div>
				</div>

				<div class="form-actions">
                	<input type="hidden" name="form_send_mandat" id="form_send_mandat" />
					<button type="submit" class="btn"><?=$this->lng['profile']['valider']?></button>
				</div>
			</form>
		</div>
	</div>
	<!-- /popup-cnt -->

</div>
