<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">
            	<?=$this->fireView('../blocs/breadcrumb')?>
				<h1><?=$this->tree->title?></h1>
				<?=$this->content['contenu-34']?>
					
				<div class="content-columns">
					<div class="column">
						<h2><?=$this->content['sous-titre-1']?></h2>
						<?=$this->content['sous-contenu-1']?>
					</div><!-- /.column -->
					<div class="column">
						<h2><?=$this->content['sous-titre-2']?></h2>
						<?=$this->content['sous-contenu-2']?>
					</div><!-- /.column -->
				</div><!-- /.content-columns -->

				<div class="tc">
					<a href="<?=$this->lurl.'/'.$this->tree->getSlug($this->content['redirection-38'],$this->language)?>" class="btn"><?=$this->content['call-to-action-37']?> <i class="icon-arrow-next"></i></a>
				</div><!-- /.tc -->
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->