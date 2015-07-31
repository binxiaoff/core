<?
if(isset($_SESSION['lexpress']))
{
	?>
	<iframe name="lexpress" SRC="<?=$_SESSION['lexpress']['header']?>" scrolling="no" height="138px" width="100%" FRAMEBORDER="no"></iframe>
	<?
}
?>
<div class="wrapper">
	
		<div class="header">
			<div class="shell clearfix">
				<div class="logo">
                	<a href="<?=$this->lurl?>">Unilend</a>
                </div><!-- /.logo -->
                <?
				if($this->clients->checkAccess())
				{
					$this->fireView('../blocs/header-account');
				}
				else
				{
					?>
					<div class="login-panel">
                    	<p class="error_login"><?=$this->error_login?></p>
						<form action="" method="post" id="form_connect">
							<span class="headConnect" ><?=$this->lng['header']['se-connecter']?></span>
							<input type="text" name="login" value="<?=$this->lng['header']['identifiant']?>" title="<?=$this->lng['header']['identifiant']?>" class="field field-tiny" style="width:129px;">
							<span class="pass-field-holder">
								<input type="password" name="password" title="<?=$this->lng['header']['mot-de-passe']?>" class="field field-tiny">
							</span>
							<button type="submit" name="connect" class="btn btn-mini btn-warning"><?=$this->lng['header']['ok']?></button>
						</form>
                        <div style="clear:both;"></div>
                       
                        <a class="popup-link lienHeader" style="margin-right:65px;" href="<?=$this->lurl?>/thickbox/pop_up_mdp"><?=$this->lng['header']['mot-de-passe-oublie']?></a>
                         <a class="lienHeader" style="margin-right:75px;" href="<?=$this->lurl.'/'.$this->tree->getSlug(127,$this->language)?>"><?=$this->lng['header']['se-creer-un-compte']?></a>
					</div><!-- /.login-panel -->
					<?
				}
				?>
				<div class="navigation">
                    <div class="shell clearfix">
                        <ul>
                            <li class="active nav-item-home" style="margin-top:15px;"><a href="<?=$this->lurl?>"><i class="icon-home"></i></a></li>
                            <?
                            foreach($this->tree->getNavigation(1,$this->language) as $key => $n)
                            {
                                ?><li><?
                                
                                $sNav = $this->tree->getNavigation($n['id_tree'],$this->language);
                                if($sNav != false && $n['id_template'] != 2)
                                {
                                    ?><ul><?
                                    foreach($sNav as $key => $sn)
                                    {
                                        ?><li><a <?=($this->tree->id_tree==$sn['id_tree']?'class="active"':'')?> href="<?=$this->lurl.'/'.$sn['slug']?>"><?=$sn['title']?></a></li><?      
                                    }
                                    ?></ul><?
                                }
                                
                                ?><a <?=($this->tree->id_tree==$n['id_tree'] || $this->tree->id_parent == $n['id_tree'] || $this->navigateurActive == $n['id_tree']?'class="active"':'')?> href="<?=$this->lurl.'/'.$n['slug']?>"><?=$n['title']?></a>
                                
                                </li><?
                            }
                            ?>
                        </ul><!-- /.nav-main -->
    
                        <div class="search">
                            <form action="<?=$this->lurl?>/search" method="post">
                                <input type="text" name="search" value="<?=$this->lng['header']['recherche']?>" title="<?=$this->lng['header']['recherche']?>" class="field field-mini">
                                <button type="submit" class="icon-search"></button>
                            </form>
                        </div><!-- /.search -->
                    </div><!-- /.shell -->
				</div><!-- /.navigation -->

			</div><!-- /.shell -->
		</div><!-- /.header -->