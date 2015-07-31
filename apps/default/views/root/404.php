<?
$this->settings->get('404 - link empruntez','type');
$empruntez = $this->settings->value;

$this->settings->get('404 - link pretez','type');
$pretez = $this->settings->value;

$this->settings->get('404 - link home','type');
$home = $this->settings->value;
?>


<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="banner higher">
				<div class="banner-content">
					<span class="pointer-left"></span>
					<span class="pointer-right"></span>
					<span class="pointer-down"></span>
					<h3>oops !</h3>
					<h2>la page que vous recherchez <br />n'existe pas ...</h2>
					<a href="<?=$this->lurl?><?=$pretez?>" class="btn btn-mega btn-info left">
						<i class="icon-arrow-medium-next right"></i>
						PRÊTez
						<small>et recevez des intérêts</small>
					</a>
					<a href="<?=$this->lurl?><?=$empruntez?>" class="btn btn-mega right">
						<i class="icon-arrow-medium-next right"></i>
						Empruntez
						<small>simplement et rapidement</small>
					</a>
					<p>Où désirez-vous aller ?</p>
					<a href="<?=$this->lurl?><?=$home?>" class="btn btn-mega btn-stand-alone">
						<i class="icon-arrow-medium-next right"></i>
						Accueil
					</a>
				</div><!-- /.banner-content -->
			</div><!-- /.banner -->
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->