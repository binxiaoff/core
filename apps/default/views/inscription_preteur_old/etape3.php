<div class="main form-page form-page-last">
    <div class="shell">

        <?=$this->fireView('../blocs/inscription-preteur')?>

        <div class="register-form">
                <div class="form-cols clearfix">
                    <h5 style="font-weight: normal;"><?=$this->lng['etape3']['choisissez-le-mode-de-versement-qui-vous-convient']?></h5>
					<form action="" method="post" id="form_preteur_cb">
                    <div class="form-col form-col-pink left">
                        <div class="form-col-inner">
                            <p><?=$this->lng['etape3']['depuis-un-de-vos-comptes-bancaires']?></p>
							
                            <div class="row row_amount">
                                <label for="amount"><?=$this->lng['etape3']['montant-a-ajouter']?></label>

                                <input type="text" class="field field-small required" value="" name="amount" id="amount" data-validators="Presence&amp;Numericality, { maximum:10000 }&amp;Numericality, { minimum:20 }" onkeyup="lisibilite_nombre(this.value,this.id);"/>
                            </div><!-- /.row -->

                            <div class="row row-cards">
                                <label for="accepted-cards"><?=$this->lng['etape3']['carte-de-credit']?></label>

                                <img src="<?=$this->surl?>/styles/default/preteurs/images/accepted-cards.png" alt="accepted-cards" />
                            </div><!-- /.row -->

                            <div class="form-actions">
                            	<input type="hidden" name="send_form_preteur_cb" />
                                <button class="btn" type="button" onClick="$('#form_preteur_cb').submit();"><?=$this->lng['etape3']['valider']?> <i class="icon-arrow-next"></i></button>
                            </div><!-- /.form-actions -->
                        </div><!-- /.form-col-inner -->
                    </div><!-- /.form-col -->
					</form>
                    <form action="" method="post" id="form_preteur_virement">
                    <div class="form-col right">
                        <div class="form-col-inner">
                            <img src="<?=$this->surl?>/styles/default/preteurs/images/logo.png" alt="print" class="printimg" />

                            <?php /*?><p><a href="#" class="btn-print right"></a><?=$this->lng['etape3']['depuis-le-compte-bancaire-renseigne']?></p><?php */?>
                            <p><a target="_blank" href="<?=$this->lurl?>/inscription_preteur/print" class="btn-print right"></a><?=$this->lng['etape3']['depuis-le-compte-bancaire-renseigne']?></p>

                            <p><span><?=$this->lng['etape3']['votre-argent-apparaitra']?></span></p>

                            <ul>
                                <li>
                                    <span style="color:#B20066;"><?=$this->lng['etape3']['motif']?></span>

                                    <strong>
                                        <?=$this->motif?>

                                        <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape3']['motif-description']?>"></i>
                                    </strong>
                                    <br />

                                    <?php /*?><em><?=$this->lng['etape3']['obligatoire-permet-laffectation-sur-votre-compte']?></em><?php */?>
                                </li>

                                <li>
                                    <span><?=$this->lng['etape3']['bic']?></span>

                                    <p><?=strtoupper($this->bic)?></p>
                                </li>

                                <li>
                                    <span><?=$this->lng['etape3']['iban']?></span>

                                    <p>
										<?=strtoupper($this->iban1)?>
                                       	<?=$this->iban2?>
                                        <?=$this->iban3?>
                                        <?=$this->iban4?>
                                        <?=$this->iban5?>
                                        <?=$this->iban6?>
                                        <?=$this->iban7?>
                                    </p>
                                </li>
                            </ul>

                            <strong><?=$this->lng['etape3']['compte-a-crediter']?></strong>

                            <ul>
                                <li>
                                    <span><?=$this->lng['etape3']['titulaire-du-compte']?></span>

                                    <p><?=$this->titulaire?></p>
                                </li>

                                <li>
                                    <span><?=$this->lng['etape3']['domiciliation']?></span>

                                    <p><?=$this->domiciliation?></p>
                                </li>
                            </ul>
							<input type="hidden" name="send_form_preteur_virement" />
                            <button class="btn" type="submit"><?=$this->lng['etape3']['virement']?> <i class="icon-arrow-next"></i></button>
                        </div><!-- /.form-col-inner -->

                        <div class="form-col-bottom">
                            <h4><?=$this->lng['etape3']['astuce']?> </h4>

                            <p><?=$this->lng['etape3']['pour-preter-regulierement']?></p>
                        </div><!-- /.form-col-bottom -->
                    </div><!-- /.form-col -->
                    </form>
                </div><!-- /.form-cols clearfix -->
            
        </div><!-- /.register-form -->
    </div><!-- /.shell -->
</div>