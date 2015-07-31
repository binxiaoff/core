<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">
            	<?
               	if($this->tree->arbo == 0)
                {
					echo $this->fireView('../blocs/breadcrumb');
				}
				?>
                <h1><?=$this->tree->title?></h1>
				<?=$this->content['contenu-1']?>

				<h2><?=$this->content['titre-2']?></h2>
				<?=$this->content['contenu-2']?>

				<h3><?=$this->content['titre-3']?></h3>
				<?=$this->content['contenu-3-1']?>
				
                <?
				if($this->content['contenu-3-bloc'] != '')
				{
					?><blockquote><?=$this->content['contenu-3-bloc']?></blockquote><?
				}
				?>

				<?=$this->content['contenu-3-2']?>

				<h4><?=$this->content['titre-4']?></h4>
				<?=$this->content['contenu-4-1']?>
				
				<div class="figures">
					<?
					if($this->content['image-4-1'] != '')
					{
						?>
						<div class="figure">
							<img src="<?=$this->photos->display($this->content['image-4-1'],'','img_contenu1')?>" alt="<?=$this->complement['image-4-1']?>">
							<span class="figure-caption"><?=$this->content['texte-image-4-1']?></span>
						</div><!-- /.figure -->
						<?
					}
					if($this->content['image-4-2'] != '')
					{
						?>
                        <div class="figure">
                            <img src="<?=$this->photos->display($this->content['image-4-2'],'','img_contenu1')?>" alt="<?=$this->complement['image-4-2']?>" >
                            <span class="figure-caption"><?=$this->content['texte-image-4-2']?></span>
                        </div><!-- /.figure -->
                        <?
					}
					if($this->content['image-4-1'] != '')
					{
						?>
                        <div class="figure">
                            <img src="<?=$this->photos->display($this->content['image-4-3'],'','img_contenu1')?>" alt="<?=$this->complement['image-4-3']?>" >
                            <span class="figure-caption"><?=$this->content['texte-image-4-3']?></span>
                        </div><!-- /.figure -->
                    <?
					}
					?>
				</div><!-- /.figures -->

				<?=$this->content['contenu-4-2']?>

				<h5><?=$this->content['titre-5']?></h5>
				<?=$this->content['contenu-5']?>

				<h6><?=$this->content['titre-6']?></h6>
				<?=$this->content['contenu-6']?>
				
                <?
				if($this->content['call-to-action'] != '' && $this->content['redirection'] != '')
				{
					?>
					<div class="tc">
						<a href="<?=$this->lurl.'/'.$this->tree->getSlug($this->content['redirection'],$this->language)?>" class="btn"><?=$this->content['call-to-action']?> <i class="icon-arrow-next"></i></a>
					</div><!-- /.tc -->
					<?
				}
				?>

			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->

<?
// pour la page de confirmation depot de dossier
if($this->tree->id_tree == '48')
{
	?>
    <!-- Google Code for inscription Empruntez Conversion Page -->
	<script type="text/javascript">
    /* <![CDATA[ */
    var google_conversion_id = 990740266;
    var google_conversion_language = "en";
    var google_conversion_format = "3";
    var google_conversion_color = "ffffff";
    var google_conversion_label = "Ku73CNbliwgQqv612AM";
    var google_conversion_value = 0;
    var google_remarketing_only = false;
    /* ]]> */
    </script>
    <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
    </script>
    <noscript>
    <div style="display:inline;">
    <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/990740266/?value=0&amp;label=Ku73CNbliwgQqv612AM&amp;guid=ON&amp;script=0"/>
    </div>
    </noscript>
    <?
}
?>
