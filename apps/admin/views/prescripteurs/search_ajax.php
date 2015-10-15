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
        <h1>Recherche : <?=$this->params['0']?></h1>            
        
        <?php if (true !== empty($this->aClients)) : ?>

		<table style="margin-bottom:15px;">

			<?php foreach ($this->aClients as $c) : ?>
			<tr>
				<td>
					<input type="hidden" id="id_prescripteur_change_<?=$c['id_prescripteur']?>">
					<input type="hidden" id="civilite_change_<?=$c['id_prescripteur']?>" value="<?=$c['civilite']?>">
					<input type="hidden" id="prenom_change_<?=$c['id_prescripteur']?>" value="<?=$c['prenom']?>">
					<input type="hidden" id="nom_change_<?=$c['id_prescripteur']?>" value="<?=$c['nom']?>">
					<input type="hidden" id="email_change_<?=$c['id_prescripteur']?>" value="<?=$c['email']?>">
					<input type="hidden" id="telephone_change_<?=$c['id_prescripteur']?>" value="<?=$c['telephone']?>">
					<input class="radio" type="radio" name="prescripteurs" id="prescripteur_<?=$c['id_prescripteur']?>" value="<?=$c['id_prescripteur']?>">
					<label for="prescripteur_<?=$c['id_prescripteur']?>"><?=$c['prenom']?> <?=$c['nom']?></label>
            	</td>
			</tr>
			<?php endforeach; ?>
        </table>
        <button id="valider_search_prescripteur" style="float:right" class="btn_link">Sélectioner</button>
		<?php else : ?>
			<p>Aucun résultat pour <?=$this->params['0']?></p>
		<?php endif; ?>
        <div class="clear"></div>
</div>

<script>
	$("#valider_search_prescripteur").click(function(){

		parent.$.fn.colorbox.close();
		var id 			= $('input[name=prescripteurs]:checked').val();
		var civilite 	= $("#civilite_change_" + id).val();
		var prenom 		= $("#prenom_change_" + id).val();
		var nom 		= $("#nom_change_" + id).val();
		var email 		= $("#email_change_" + id).val();
		var telephone 	= $("#telephone_change_" + id).val();

		$("#id_prescripteur").val(id);
		$("#civilite_prescripteur").html(civilite);
		$("#prenom_prescripteur").html(prenom);
		$("#nom_prescripteur").html(nom);
		$("#email_prescripteur").html(email);
		$("#telephone_prescripteur").html(telephone);

		$('.identification_prescripteur').show('slow');

		$("#search").val('');
	});
</script>