<?
if(!isset($this->params[0]) || $this->params[0] == '')
{
	?>
    <script>
	parent.$.colorbox.close();
	</script>
	<?
}
?>


<div id="popup">
	<a onclick="parent.$.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
        <h1>Recherche : <?=$this->params['0']?></h1>

        <?
        if($this->lClients != false)
		{
        echo '<table style="margin-bottom:15px;">';

		foreach($this->lClients as $c)
		{
			?><tr><td>
            <input type="hidden" id="prenom_change_<?=$c['id_client']?>" value="<?=$c['prenom']?>">
            <input type="hidden" id="nom_change_<?=$c['id_client']?>" value="<?=$c['nom']?>">
            <input class="radio" type="radio" name="clients" id="client_<?=$c['id_client']?>" value="<?=$c['id_client']?>"> <?=$c['prenom']?> <?=$c['nom']?>
            </td></tr><?
		}
		?>
        </table>
        <button id="valider_search" style="float:right" class="btn_link" onclick="parent.$.colorbox.close();">Valider</button><?
		}
		else
		{
			?><p>Aucun résultat pour <?=$this->params['0']?></p><?
		}
		?>
        <div class="clear"></div>
</div>

<script>
	$("#valider_search").click(function(){
		var id = $('input[name=clients]:checked').val();
		var prenom = $("#prenom_change_"+id).val();
		var nom = $("#nom_change_"+id).val();

		$("#id_client").val(id);
		$("#prenom").val(prenom);
		$("#nom").val(nom);

		$("#prenomHtml").html(prenom);
		$("#nomHtml").html(nom);
		$("#id_clientHtml").html(id);


		$("#search").val('');





	});
</script>