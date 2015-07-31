<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">
            	<?=$this->fireView('../blocs/breadcrumb')?>
				<div class="posts">
					<h1><?=$this->tree->title?></h1>
					<?
                    echo $this->content['contenu'];
					if($this->childsContent != false)
					{
						foreach($this->childsContent as $k => $c)
						{
						?>
						<div class="entry">
							<?
							if($c['image'] != false)
							{
								?><img src="<?=$this->photos->display($c['image'],'','img_contenu2')?>" alt="<?=$this->childsComplement[$k]['image']?>" class="entry-thumb" width="226" height="157"><?
							}
							?>
							<div class="entry-body">
								<?=$c['contenu-30']?>
							</div><!-- /.entry-body -->
						</div><!-- /.entry -->
						<?
						}
					}
					?>
				</div><!-- /.posts -->
				<?
				if($this->content['call-to-action-27'] != '')
				{
					?>
					<div class="tc">
						<a href="<?=$this->lurl?>/<?=$this->tree->getSlug($this->content['redirection-28'],$this->language)?>" class="btn"><?=$this->content['call-to-action-27']?> <i class="icon-arrow-next"></i></a>
					</div><!-- /.tc -->
					<?
				}
				?>
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->