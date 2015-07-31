<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
        	<?
			//unset($_SESSION['qs']);
			// ON CHECK si on a une question ou reponse
			if(in_array('',array($this->clients->secrete_question,$this->clients->secrete_reponse)) && $_SESSION['qs'] != date('d') || isset($_SESSION['qs_ok']) && $_SESSION['qs_ok'] == 'OK'){
				?>
					<script type="text/javascript">
						$.colorbox({ href:add_url+"/thickbox/pop_up_qs", opacity: 0.5, scrolling: false });
					</script>
				<?
				$_SESSION['qs'] = date('d');
			}
			?>
        
			<div class="shell">
				<div class="section-c dashboard clearfix">
					<div class="page-title clearfix">
						<h1 class="left"><?=$this->lng['preteur-synthese']['votre-tableau-de-bord']?></h1>
						<strong class="right">au <?=$this->dates->formatDateComplete(date('Y-m-d H:i:s'))?> à <?=date('H\hi')?></strong>
					</div>
                    
                    <!--------------------------------------------------->
                    <?
					// cgv
					/*if(!in_array($this->clients->id_client,array(1,12))){
						$this->accept_ok = true; // temporaire
					}*/
					//if(in_array($this->clients->id_client,array(1,12)))
					
					// LE TEMPS QU'ON AIT LA POPUP CGV ON MASQUE CE BLOC
					
					if($this->accept_ok == false){
						?>
						<div class="notification-primary">
							<div class="notification-head">
								<h3 class="notification-title"><?=$this->bloc_cgv['titre-242']?></h3><!-- /.notification-title -->
							</div><!-- /.notification-head -->
							
							<div class="notification-body">
                                <?
								// mise a jour cgv
								if($this->update_accept == true) 
									echo $this->bloc_cgv['content-2'];
								else 
									echo $this->bloc_cgv['content-1'];
								?>
								<div class="form-terms">
									<form action="" method="post">
										<div class="checkbox">
											<input type="checkbox" name="terms" id="terms" />
											
                                            <label for="terms"><a target="_blank" href="<?=$this->lurl.'/cgv_preteurs/nosign'?>"><?=$this->bloc_cgv['checkbox-cgv']?></a></label>
											<?php /*?><label for="terms"><a target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->bloc_cgv['checkbox-cgv']?></a></label><?php */?>      
										</div><!-- /.checkbox -->
	
										<div class="form-actions">
											<button type="button" id="cta_cgv" class="btn form-btn">
												<?=$this->bloc_cgv['cta-valider']?>
	
												<i class="ico-arrow"></i>
											</button>
										</div><!-- /.form-actions -->
									</form>
								</div><!-- /.form-terms -->
							</div><!-- /.notification-body -->
						</div><!-- /.notification-primary -->
						<script type="text/javascript">
						$( "#cta_cgv" ).click(function() {
							if($("#terms").is(':checked') == true){
								$.post( add_url+"/ajax/accept_cgv", { terms: $("#terms").val(), id_legal_doc: "<?=$this->lienConditionsGenerales?>" }).done(function( data ) {
									$(".notification-primary").fadeOut(); setTimeout(function() { $(".notification-primary").remove(); }, 1000);
								});
							}
							else{ $(".checkbox a").css('color','#c84747'); }
						});
						$( "#terms" ).change(function() { if($(this).is(':checked') == true){ $(".checkbox a").css('color','#727272');} });
						</script>
						<?
					}
					?>
                    <!------------------------------------------------------>
                    
                    <?
					if($this->nblFavP>0 && !isset($_SESSION['lFavP']))
					{
					?>
					<div class="natification-msg">
						<h3><?=$this->lng['preteur-synthese']['notifications']?></h3>
						<ul>
                        	<?
							if($this->nblFavP>0)
							{
								?>
								<li><strong><?=$this->lng['preteur-synthese']['mes-favoris']?> :</strong>
									<ul>
									<?
									foreach($this->lFavP as $f)
									{
										$this->projects->get($f['id_project'],'id_project');
										$_SESSION['lFavP'] = true;
										?>
										<li>
										<?=($f['datediff']>0?'Plus que '.$f['datediff'].' jours':'Dernier jour')?> 
										
										<?=$this->lng['preteur-synthese']['pour-faire-une-offre-de-pret-sur-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$f['title']?></a>.
										</li>
										<?
									}
									?>
									</ul>
								</li>
								<?
							}
							/*if($this->nblRejetB>0)
							{
								?>
								<li><strong><?=$this->lng['preteur-synthese']['encheres-rejetes']?> : </strong>
									<ul>
									<?
                                    foreach($this->lRejetB as $r)
                                    {
										$this->bids->get($r['id_bid'],'id_bid');
										$this->projects->get($r['id_project'],'id_project');
										
										if($this->bids->amount != $r['amount'])
										{
											$montant = ($this->bids->amount - $r['amount']);
											?><li><?=$this->lng['preteur-synthese']['attentions-votre-offre-de-pret-a']?><b> <?=number_format($this->bids->rate,2,',',' ')?></b><?=$this->lng['preteur-synthese']['pour-un-montant-de']?><b> <?=number_format($this->bids->amount/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['sur-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$this->projects->title?></a><?=$this->lng['preteur-synthese']['a-ete-decoupe']?> <b><?=number_format($r['amount']/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['vous-ont-ete-rendu']?></li><?
										}
										else
										{
                                        ?><li><?=$this->lng['preteur-synthese']['attentions-votre-offre-de-pret-a']?><b> <?=number_format($this->bids->rate,2,',',' ')?></b><?=$this->lng['preteur-synthese']['pour-un-montant-de']?><b> <?=number_format($r['amount']/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['sur-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$this->projects->title?></a><?=$this->lng['preteur-synthese']['nest-plus-recevable']?></li><?
										}
                                    }
                                    ?>
                                	</ul>
								</li>
								<?
							}
							if($this->nblRembB>0)
							{
							?>
                            <li><strong><?=$this->lng['preteur-synthese']['remboursements']?> : </strong>
                            	<ul>
									<?
                                    foreach($this->lRembB as $r)
                                    {
										$this->projects->get($r['id_project'],'id_project');
										
										?><li><?=$this->lng['preteur-synthese']['vous-venez-de-recevoir-un-remboursement-de']?> <b><?=number_format($r['amount']/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['pour-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$this->projects->title?></a></li><?
									}
									?>
                            	</ul>
                            </li>
                            <?
							}*/
							?>
						</ul>
						<a class="esc-btn" href="#"></a>
					</div>
                    <?
					}
					?>

					<div class="col left">
                        <?=$this->fireView('../blocs/synthese-preteur-bloc-gauche')?>
					</div>

					<div class="col right">
						<?=$this->fireView('../blocs/synthese-preteur-bloc-droit')?>
					</div>

					
				</div>
			</div>
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->
<script type="text/javascript">
$('.chart-slider').carouFredSel({
	width: 420,
	height: 260,
	auto: false,
	prev: '.slider-c .arrow.prev',
	next: '.slider-c .arrow.next',
	items: {
		visible: 1
	}
});
</script>
