<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Accès Interdit</h1>
    <strong>Vous n'avez pas les droits pour accéder à cette page !</strong><br /><br />
    Contacter l'administrateur pour toute information complémentaire.
</div>
<?php unset($_SESSION['msgErreur']); ?>