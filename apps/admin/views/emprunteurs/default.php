<script type="text/javascript">
	<?
	if(isset($_SESSION['freeow']))
	{
	?>
		$(document).ready(function(){
			var title, message, opts, container;
			title = "<?=$_SESSION['freeow']['title']?>";
			message = "<?=$_SESSION['freeow']['message']?>";
			opts = {};
			opts.classes = ['smokey'];
			$('#freeow-tr').freeow(title, message, opts);
		});
	<?
	}
	?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li>Arbo emprunteurs</li>
    </ul>
	<h1>Arbo emprunteurs</h1>
    <p id="masstoggler"><a title="Tout r&eacute;duire">Tout r&eacute;duire</a>&nbsp;|&nbsp;<a title="Tout ouvrir">Tout ouvrir</a></p>
    <div><?=$this->tree->getArbo(($_SESSION['user']['id_tree'] != 0?$_SESSION['user']['id_tree']:1),$this->language,2)?></div>
</div>
<?php unset($_SESSION['freeow']); ?>