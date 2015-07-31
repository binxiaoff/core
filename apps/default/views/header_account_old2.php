<?
	// header personalisé pour l'express
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
            <div class="logo"><a href="<?=$this->lurl?>"><?=$this->lng['header']['unilend']?></a></div><!-- /.logo -->
            <?=$this->fireView('../blocs/header-account')?>


        </div><!-- /.shell -->
    </div><!-- /.header -->
<?
// preteur
if($this->clients->status_pre_emp == 1)
{
	?>
	<style type="text/css">
    .navigation .styled-nav{width: 100%;}
    </style>
    <div class="navigation ">
        <div class="shell clearfix">
            <ul class="styled-nav">
            	<li class="active nav-item-home" style="position: relative;top: 10px;height: 16px;overflow:hidden;"><a href="<?=$this->lurl?>"><i class="icon-home"></i></a></li>
            
                <li><a <?=($this->page=='synthese'?'class="active"':'')?> href="<?=$this->lurl?>/synthese"><?=$this->lng['header']['synthese']?></a></li>
                <li><a <?=($this->page=='alimentation'?'class="active"':'')?> href="<?=$this->lurl?>/alimentation"><?=$this->lng['header']['alimentation']?></a></li>
                <li><a <?=($this->page=='projects'?'class="active"':'')?> href="<?=$this->lurl?>/projects"><?=$this->lng['header']['projets']?></a></li>
                <li><a <?=($this->page=='mouvement'?'class="active"':'')?> href="<?=$this->lurl?>/mouvement"><?=$this->lng['header']['operations']?></a></li>
                <li><a <?=($this->page=='profile'?'class="active"':'')?> href="<?=$this->lurl?>/profile"><?=$this->lng['header']['mon-profil']?></a></li>
            </ul><!-- /.nav-main -->

            
        </div><!-- /.shell -->
    </div><!-- /.navigation -->
    <?
}
// emprunteur
else
{
	?>
	<style type="text/css">
    .navigation .styled-nav{width: 713px;}
    </style>
 
	<?
    if($this->etape_transition == true)
	{
		?>
		<div class="navigation ">
            <div class="shell">
                <h1><?=$this->tree->title?></h1>
            </div>
        </div>
		<?
	}
	else
    {
        ?>
        <div class="navigation ">
            <div class="shell clearfix">
                <ul class="styled-nav">
                    <li><a <?=($this->page=='synthese'?'class="active"':'')?> href="<?=$this->lurl?>/synthese_emprunteur"><?=$this->lng['header']['synthese']?></a></li>
                    <?
                    if($this->nbProjets>1)
                    {
                    ?>
                    <li><a <?=($this->page=='projects'?'class="active"':'')?> href="<?=$this->lurl?>/projects_emprunteur"><?=$this->lng['header']['projets']?></a></li>
                    <?
                    }
                    ?>
                    <li><a <?=($this->page=='societe'?'class="active"':'')?> href="<?=$this->lurl?>/societe_emprunteur"><?=$this->lng['header']['societe']?></a></li>
                    <li><a <?=($this->page=='unilend_emprunteur'?'class="active"':'')?> href="<?=$this->lurl?>/unilend_emprunteur"><?=$this->lng['header']['unilend']?></a></li>
                </ul><!-- /.nav-main -->
    
                <a class="outnav right" href="<?=$this->lurl?>/create_project_emprunteur"><span><?=$this->lng['header']['nouveau-projet']?></span></a>
            </div><!-- /.shell -->
        </div><!-- /.navigation -->
        <?
    }
}
?>