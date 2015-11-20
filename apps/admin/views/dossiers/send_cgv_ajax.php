<?
if(!isset($this->params[0]) || $this->params[0] == '')
{
	?>
    <script>
	parent.$.fn.colorbox.close();
	</script>
	<?
}
?>


<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<h1>Resultat d'envoi des CGV</h1>
	<p><?=$this->result?></p>
	<div class="clear"></div>
</div>