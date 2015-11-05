<h2><?=$this->lng['preteur-operations']['titre-vos-documents']?></h2>
<p><?=$this->lng['preteur-operations']['desc-vos-documents']?></p>


<?
if(count($this->liste_docs) > 0)
{
	?>
	<div class="content_table_vos_documents">
		<table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<th id="order_operations"  class="narrow-th" width="105">
					<b><?=$this->lng['preteur-operations']['vos-documents-nom-colonne']?></b>
				</th>
				<th width="50">
					<div class="th-wrap"><i title="<?=$this->lng['profile']['info-8']?>" class="icon-empty-folder tooltip-anchor"></i></div>
				</th>
			</tr>

			<?
			$i=1;
			foreach($this->liste_docs as $doc)
			{
				$annee = $doc['annee'];
				$annee_suivante = $annee + 1;

				//trad : Imprimé Fiscal Unique - Revenus $annee ( Déclaration $annee_suivante)
				$libelle = $this->lng['preteur-operations']['imprime-fiscal-unique-revenus'];
				// on remplace les var de la trad
				eval("\$libelle = \"$libelle\";");


				?>
				<tr <?=($i%2 == 1?'':'class="odd"')?>>
					<td><?=$libelle?></td>
					<td>
						<a class="tooltip-anchor icon-pdf" href="<?=$this->lurl.'/operations/get_ifu/'.$this->clients->hash.'/'.$annee?>"></a>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<h2><?=$this->lng['preteur-operations']['titre-vos-documents-isf']?></h2>
        <?
		$annee = date('Y');
		$annee_prec = $annee-1;
		$solde = current(current($this->bdd->run("SELECT solde FROM indexage_vos_operations WHERE id_client = ".$this->clients->id_client." AND date_operation < '$annee-01-01 00:00:00' ORDER BY date_operation DESC LIMIT 0,1")))/100 + current(current($this->bdd->run("SELECT sum(amount) FROM bids INNER JOIN `lenders_accounts` ON lenders_accounts.id_lender_account = bids.id_lender_account WHERE lenders_accounts.id_client_owner = ".$this->clients->id_client." AND bids.added < '$annee-01-01 00:00:00' AND bids.updated >= '$annee-01-01 00:00:00'")))/100;

		$projects_en_remboursement = $this->bdd->run("SELECT id_project FROM `projects_status_history` WHERE id_project_status = 8 AND added < '$annee-01-01 00:00:00'");
		foreach($projects_en_remboursement as $key=>$value)$projects_en_remboursement[$key] = $value['id_project'];

		$capital_du = current(current($this->bdd->run("SELECT sum(capital) FROM `echeanciers` INNER JOIN `lenders_accounts` ON lenders_accounts.id_lender_account = echeanciers.id_lender WHERE (date_echeance_reel >= '$annee-01-01 00:00:00' OR echeanciers.status = 0) AND id_project IN(".implode(',',$projects_en_remboursement).") AND lenders_accounts.id_client_owner = ".$this->clients->id_client)))/100;

		$solde = $this->ficelle->formatNumber($solde);
		$capital_du = $this->ficelle->formatNumber($capital_du);

		$texte = $this->lng['preteur-operations']['texte-vos-documents-isf'];
		eval("\$texte = \"$texte\";");
		echo nl2br($texte);?>

		<?php /*?><script type="text/javascript">
		$("#order_date").click(function() {

			if($(this).attr('id') == 'order_date'){
				var type = 'order_date';

				if($("#order_date.asc").length){ var order = 'desc'; }
				else{ var order = 'asc'; }
			}

			$(".load_table_vos_operations").fadeIn();

			var val = {
				debut 				: $("#debut").val(),
				fin 				: $("#fin").val(),
				nbMois 				: $("#nbMois").val(),
				annee 				: $("#annee").val(),
				tri_type_transac 	: $("#tri_type_transac").val(),
				tri_projects 		: $("#tri_projects").val(),
				id_last_action		: $(this).attr('id'),
				order 				: order,
				type 				: type
			}

			$.post(add_url+"/ajax/vos_operations",val).done(function( data ) {

				var obj = jQuery.parseJSON(data);

				$("#debut").val(obj.debut);
				$("#fin").val(obj.fin);

				$(".content_table_vos_operations").html(obj.html);
				$(".load_table_vos_operations").fadeOut();
			});
		});
		</script><?php */?>

	</div>
	<?php
}
else
{
	echo $this->lng['preteur-operations']['aucun-document'];
}