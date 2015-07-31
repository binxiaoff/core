		<div class="footer">
			<div class="footer-main">
				<div class="shell">
					<div class="footer-social">
						<div class="social-block block-twitter">
							<a target="_blank" href="<?=$this->twitter?>" class="icon"><i class="icon-big-twitter"></i></a>
							<div class="body">
								<?=$this->lng['footer']['suivez-nous-sur-twitter']?>
							</div>
						</div>

						<div class="social-block block-facebook">
							<a target="_blank" href="<?=$this->like_fb?>" class="icon">
								<i class="icon-big-facebook"></i>
							</a>

							<div class="body">
								<small><?=$this->lng['footer']['unilend']?></small>
								
							</div>
							
							<div class="foot" >
                                
                                
                                <div class="fb-like" data-href="<?=$this->like_fb?>" data-width="200" data-colorscheme="light" data-layout="standard" data-action="like" data-show-faces="true" data-send="false"></div>
                                
							</div>
						</div>
					</div><!-- /.footer-social -->

					<ul class="footer-nav">
						<li>
							<h5><?=$this->lng['footer']['titre-nav-1']?></h5>
							<ul>
                            	<?
								foreach($this->navFooter1 as $key => $nf)
                                {
                                    ?><li><a target="<?=$nf['target']?>" href="<?=$nf['url']?>"><?=$nf['nom']?></a></li><?
                                }
                                ?>
							</ul>
						</li>
						<li>
							<h5><?=$this->lng['footer']['titre-nav-2']?></h5>
							<ul>
								<?
								foreach($this->navFooter2 as $key => $nf)
                                {
                                    ?><li><a target="<?=$nf['target']?>" href="<?=$nf['url']?>"><?=$nf['nom']?></a></li><?
                                }
                                ?>
							</ul>
						</li>
						<li class="wide">
							<h5><?=$this->lng['footer']['titre-nav-3']?></h5>
							<ul>	
								<?
								foreach($this->navFooter3 as $key => $nf)
                                {
                                    ?><li><a target="<?=$nf['target']?>" href="<?=$nf['url']?>"><?=$nf['nom']?></a></li><?
                                }
                                ?>
							</ul>
						</li>
						<li>
							<h5><?=$this->lng['footer']['titre-nav-4']?></h5>
							<ul>
								<?
								foreach($this->navFooter4 as $key => $nf)
                                {
                                    ?><li><a target="<?=$nf['target']?>" href="<?=$nf['url']?>"><?=$nf['nom']?></a></li><?
                                }
                                ?>
							</ul>
						</li>
					</ul><!-- /.footer-nav -->
					<p class="copyrights"><?=$this->lng['footer']['copyrights']?>
                    
                    <?
					$i = 0;
					foreach($this->menuFooter as $key => $f)
					{
						?><?=($i==0?'':' | ')?><a target="<?=$f['target']?>" href="<?=$f['url']?>"><?=$f['nom']?></a><?
						$i++;
					}
					?>
                    
                    </p><!-- /.copyrights -->

				</div><!-- /.shell -->
			</div><!-- /.footer-main -->
			<div class="footer-partners">
				<div class="shell">
					<h6>Nos partenaires</h6>
					<ul>
                    	<?
						for($i=1;$i<=4;$i++)
                        {
							if($this->bloc_partenaires['image-'.$i] != false)
							{
								if($this->bloc_partenaires['lien-'.$i] != '')
								{
                            		?><li><a target="_blank" href="<?=$this->bloc_partenaires['lien-'.$i]?>"><img src="<?=$this->surl?>/var/images/<?=$this->bloc_partenaires['image-'.$i]?>" alt="<?=$this->bloc_partenairesComplement['image'.$i]?>" /></a></li> <?
								}
								else
								{
									?><li><img src="<?=$this->surl?>/var/images/<?=$this->bloc_partenaires['image-'.$i]?>" alt="<?=$this->bloc_partenairesComplement['image'.$i]?>" /></li> <?
								}
							
							}
                        }
						?>
                            <li>
                                <table width="135" border="0" cellpadding="2" cellspacing="0" title="Cliquez sur Vérifier - Ce site a choisi Symantec SSL pour un e-commerce sûr et des communications confidentielles.">
                                    <tr>
                                        <td width="135" align="center" valign="top">
                                        <script type="text/javascript" src="https://seal.verisign.com/getseal?host_name=www.unilend.fr&amp;size=XS&amp;use_flash=NO&amp;use_transparent=NO&amp;lang=fr"></script></td>
                                    </tr>
                                </table>
                            </li>
                         
					</ul>
				</div><!-- /.shell -->
			</div><!-- /.footer-partners -->
            <?
			if(isset($_SESSION['lexpress']))
			{
				if($_SESSION['lexpress']['id_template'] == 15){
					?>
					<iframe name="lexpressfooter" SRC="<?=$_SESSION['lexpress']['footer']?>" scrolling="no" height="1000px" width="100%" FRAMEBORDER="no"></iframe>
					<?
				}
				elseif($_SESSION['lexpress']['id_template'] == 19){
					?>
                    <iframe name="lexpressfooter" SRC="<?=$_SESSION['lexpress']['footer']?>" scrolling="no" height="1160px" width="100%" FRAMEBORDER="no"></iframe>
                    <?	
				}
			}
			?>
		</div><!-- /.footer -->
        
        <?php
		
			// COOKIES CNIL //
			//if($this->create_cookies == true){
				
				
				//if($_SERVER['REMOTE_ADDR'] == '93.26.42.99'){
					?>  
                    <div class="cookies">
                        <div class="content_cookies">
                        
                            <div><?=$this->lng['footer']['cookies-content']?> <a target="_blank" href="<?=$this->lurl?>/<?=$this->tree->getSlug(381,$this->language)?>"><?=$this->lng['footer']['cookies-link']?></a></div>
                        </div>
                        <div class="accept_cookies">
                            <button onclick="acceptCookies();"><?=$this->lng['footer']['cookies-cta']?></button>
                        </div>
                    </div>

                    <script type="text/javascript">
					if (document.cookie.indexOf("acceptCookie") >= 0) {
					  $('.cookies').hide();
					}
					</script>
                    <?
				//}
				/*else{
					?>  
                    <div class="cookies" <?=($this->create_cookies == true?'':'style="display:none;"')?>>
                        <div class="content_cookies">
                        
                            <div><?=$this->lng['footer']['cookies-content']?> <a target="_blank" href="<?=$this->lurl?>/<?=$this->tree->getSlug(381,$this->language)?>"><?=$this->lng['footer']['cookies-link']?></a></div>
                        </div>
                        <div class="accept_cookies">
                            <button onclick="acceptCookies();"><?=$this->lng['footer']['cookies-cta']?></button>
                        </div>
                    </div>
                    
                    <?php
				}*/
			//}
			// COOKIES CNIL //
		//}
		?>
        
        
	</div><!-- /.wrapper -->
</body>
</html>