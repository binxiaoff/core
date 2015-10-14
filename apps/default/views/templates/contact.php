<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">
            	<?=$this->fireView('../blocs/breadcrumb')?>
				<div class="contact">
					<h1><?=$this->tree->title?></h1>
					<?=$this->content['contenu-39']?>

					<div class="contact-form">
						<form action="" method="post">
							<p class="system-message message-positive">
								<?=(isset($this->confirmation)) ? $this->confirmation : ''?>
							</p><!-- /.system-message -->

							<div class="row">
								<select class="custom-select field field-extra-large <?=($this->projects->period != 0?'':'required')?>" name="demande" id="demande">
									<option <?=($this->demande_contact->demande == 0?'selected':'')?> value="0"><?=$this->lng['contact']['votre-demande-porte-sur']?></option>
									<option <?=($this->demande_contact->demande == 1?'selected':'')?> value="1"><?=$this->lng['contact']['relation-presse']?></option>
									<option <?=($this->demande_contact->demande == 2?'selected':'')?> value="2"><?=$this->lng['contact']['demande-preteur']?></option>
									<option <?=($this->demande_contact->demande == 3?'selected':'')?> value="3"><?=$this->lng['contact']['demande-emprunteur']?></option>
									<option <?=($this->demande_contact->demande == 4?'selected':'')?> value="4"><?=$this->lng['contact']['recrutement']?></option>
                                    <option <?=($this->demande_contact->demande == 5?'selected':'')?> value="5"><?=$this->lng['contact']['autre']?></option>
									<option <?=($this->demande_contact->demande == 6?'selected':'')?> value="6"><?=$this->lng['contact']['partenariat']?></option>
								</select>
							</div><!-- /.row -->

							<div class="row" <?=($this->demande_contact->demande==5?'style="display:block;"':'style="display:none;"')?> id="rowPreciser">
								<input type="text" title="<?=$this->lng['contact']['preciser']?>" name="preciser" value="<?=($this->demande_contact->preciser != false?$this->demande_contact->preciser:$this->lng['contact']['preciser'])?>" id="preciser" class="field field-small" >
							</div><!-- /.row -->

							<div class="row">
                                    <input type="text" title="<?=$this->lng['contact']['nom']?>" value="<?=($this->demande_contact->nom != false?$this->demande_contact->nom:$this->lng['contact']['nom'])?>" id="nom" name="nom" class="field field-small required <?=(isset($this->error_nom) && $this->error_nom == 'ok'?'LV_valid_field':(isset($this->error_nom) && $this->error_nom == 'nok'?'LV_invalid_field':''))?>" data-validators="Presence">
                                    <input type="text" title="<?=$this->lng['contact']['prenom']?>" value="<?=($this->demande_contact->prenom != false?$this->demande_contact->prenom:$this->lng['contact']['prenom'])?>" name="prenom" id="prenom" class="field field-small required <?=(isset($this->error_prenom) && $this->error_prenom == 'ok'?'LV_valid_field':(isset($this->error_prenom) && $this->error_prenom == 'nok'?'LV_invalid_field':''))?>" data-validators="Presence">
							</div><!-- /.row -->

							<div class="row">
                                    <input type="text" title="<?=$this->lng['contact']['email']?>" value="<?=($this->demande_contact->email != false?$this->demande_contact->email:$this->lng['contact']['email'])?>" name="email" id="email" class="field field-small required <?=(isset($this->error_email) && $this->error_email == 'ok'?'LV_valid_field':(isset($this->error_email) && $this->error_email == 'nok'?'LV_invalid_field':''))?>" data-validators="Email">
									<input type="text" title="<?=$this->lng['contact']['telephone']?>" value="<?=($this->demande_contact->telephone != false?$this->demande_contact->telephone:$this->lng['contact']['telephone'])?>" name="telephone" id="phone" class="field field-small <?=(isset($this->error_telephone) && $this->error_telephone == 'ok'?'LV_valid_field':(isset($this->error_telephone) && $this->error_telephone == 'nok'?'LV_invalid_field':''))?>">
							</div><!-- /.row -->

							<div class="row">
								<input type="text" title="<?=$this->lng['contact']['societe']?>" value="<?=($this->demande_contact->societe != false?$this->demande_contact->societe:$this->lng['contact']['societe'])?>" name="societe" id="societe" class="field field-small">
							</div><!-- /.row -->

							<div class="row">
								<textarea cols="30" rows="10" title="<?=$this->lng['contact']['message']?>" name="message" id="message" class="field field-extra-large required <?=(isset($this->error_message) && $this->error_message == 'ok'?'LV_valid_field':(isset($this->error_message) && $this->error_message == 'nok'?'LV_invalid_field':''))?>" data-validators="Presence"><?=($this->demande_contact->message != false?$this->demande_contact->message:$this->lng['contact']['message'])?></textarea>
							</div><!-- /.row -->

							<div class="row row-captcha">
								<div class="captcha-holder">
									<img src="<?=$this->surl?>/images/default/securitecode.php" alt="captcha" />
								</div><!-- /.captcha-holder -->
								<input type="text" name="captcha" class="field required <?=(isset($this->error_captcha) && $this->error_captcha == 'ok'?'LV_valid_field':(isset($this->error_captcha) && $this->error_captcha == 'nok'?'LV_invalid_field':''))?>" id="captcha" data-validators="Presence" value="<?=$this->lng['contact']['captcha']?>" title="<?=$this->lng['contact']['captcha']?>">
							</div><!-- /.row row-captcha -->

							<div class="form-foot">
                            	<input type="hidden" name="send_form_contact" id="send_form_contact" />
								<button type="submit" class="btn"><?=$this->content['call-to-action-40']?> <i class="icon-arrow-next"></i></button>
							</div><!-- /.form-foot -->

						</form>
					</div><!-- /.contact-form -->

					<div class="contact-block">
						<div class="contact-block-body">
							<h2><?=$this->content['bloc-titre']?></h2>
							<h4><i class="icon-place"></i> <?=$this->content['bloc-adresse']?></h4>

							<p><i class="icon-phone"></i> <?=$this->lng['contact']['bloc-tel']?> : <?=$this->content['bloc-telephone']?></p>
							<p><i class="icon-mail"></i> <?=$this->lng['contact']['bloc-email']?> : <a href="mailto:<?=$this->content['bloc-email']?>"><?=$this->content['bloc-email']?></a> </p>
						</div><!-- /.contact-block-body -->

						<div class="contact-block-image">
							<img src="<?=$this->photos->display($this->content['bloc-image'],'','img_contact')?>" alt="<?=$this->complement['bloc-image']?>">
						</div><!-- /.contact-block-image -->
					</div><!-- /.contact-block -->
				</div><!-- /.contact -->
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->

