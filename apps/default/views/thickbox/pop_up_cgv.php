<link media ="all" href="<?=$this->lurl?>/styles/default/synthese1.css" type="text/css" rel="stylesheet" />
<div class="popup" style="background-color: #E3E4E5;">
	<a href="#" class="popup-close">close</a>
	<div class="popup-head">
		<h2><?=$this->lng['preteur-profile']['pop-up-cgv-titre']?></h2>
	</div>

	<div class="popup-cnt">
    	<p>
        	<div class="notification-primary">
                <div class="notification-body">
                    <?

                    // mise a jour cgv
                    if($this->update_accept_header == true)
                        echo $this->bloc_cgv['content-2'];
                    else
                        echo $this->bloc_cgv['content-1'];
                    ?>
                    <div class="form-terms">
                        <form action="" method="post">
                            <div class="checkbox checkbox_pop" >
                                <input type="checkbox" name="terms_pop" id="terms_pop"/>
                                <?
                                //if($_SERVER['REMOTE_ADDR'] == '93.26.42.99'){
                                    ?><label for="terms_pop"><a target="_blank" href="<?=$this->lurl.'/cgv_preteurs/nosign'?>"><?=$this->bloc_cgv['checkbox-cgv']?></a></label><?
                                /*}
                                else
                                {
                                    ?><label for="terms"><a target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->bloc_cgv['checkbox-cgv']?></a></label><?
                                }*/
                                ?>

                            </div><!-- /.checkbox -->

                            <div class="form-actions">

                                <button type="button" id="cta_cgv_pop" class="btn form-btn">
                                    <?=$this->bloc_cgv['cta-valider']?>

                                    <i class="ico-arrow"></i>
                                </button>
                            </div><!-- /.form-actions -->
                        </form>
                    </div><!-- /.form-terms -->
                </div><!-- /.notification-body -->
            </div><!-- /.notification-primary -->
            <script type="text/javascript">
				$( "#cta_cgv_pop" ).click(function() {
					if($("#terms_pop").is(':checked') == true){
						$.post( add_url+"/ajax/accept_cgv", { terms: $("#terms_pop").val(), id_legal_doc: "<?=$this->lienConditionsGenerales_header?>" }).done(function( data ) {
							location.reload();
						});
					}
					else{ $(".checkbox_pop a").css('color','#c84747'); }
				});
				$( "#terms_pop" ).change(function() {if($(this).is(':checked') == true){ $(".checkbox_pop a").css('color','#727272');} });

            </script>
        </p>


	</div>
	<!-- /popup-cnt -->
</div>