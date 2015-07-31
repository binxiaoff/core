<script type="text/javascript">
	$(document).ready(function(){
		$(".tableProject").tablesorter({headers:{4:{sorter: false}}});	
		$(".tableProject").tablesorterPager({container: $("#pager"),positionFixed: false,size: 5});
		
		$("#newRecherche").click(function() {
			$("#leformProject").show();
			$("#reponse").hide();
		});
	});
	
	function attribuer(id_project,id_reception)
	{
		var conf = confirm("Voulez vous vraiment attribuer la somme à ce projet ?");
		if(conf==true)
		{
			var val = { 
				id_project: id_project,
				id_reception: id_reception,
			}
			
			$.post(add_url + '/ajax/ValidAttribution_project', val).done(function(data) {
				
				
				
				if(data != 'nok')
				{
					var obj = jQuery.parseJSON(data);
					var id_client = obj.id_client;
					var id_project = obj.id_project;
					
					$(".num_project_"+id_reception).html(id_project);
					$(".num_client_"+id_reception).html(id_client);
					$(".preteur_projet_"+id_reception).html('Remb projet '+id_project);
					$(".statut_prelevement_"+id_reception).html('Attribué manu');
					$(".ajouter_"+id_reception).hide();
					$(".annuler_"+id_reception).show();
					
					$(".rejete_"+id_reception).show();
					
					var rejet = '<img class="rejete_'+id_reception+'" style="cursor:pointer;" onclick="rejeteAttribution('+id_project+','+id_reception+')" src="<?=$this->surl?>/images/admin/edit.png" alt="Rejeté" />';
					
					var annule = '<img class="annuler_'+id_reception+'" style="cursor:pointer;" onclick="annulerAttribution('+id_project+','+id_reception+')" src="<?=$this->surl?>/images/admin/delete.png" alt="Annuler Attibution" />';
					
					$(".rejet_annule_"+id_reception).html(rejet+annule);

					
					$(".reponse_valid_pre").slideDown();
					
					/*$("#leformpreteur").hide();
					$("#reponse").show();
					$("#reponse").html(data);*/
				
					setTimeout(function() {
						$(".closeBtn").click();
					}, 3000);
				}
			});
			
			
		}	
	};
	
</script>

<br><br><br>
<div class="btnDroite"><a href="#" id="newRecherche" class="btn_link">Nouvelle recherche</a></div>
<?
if(count($this->lProjects) > 0)
{
?>
	<table class="tablesorter tableProject">
		<thead>
			<tr>
				<th>Id projet</th>
				<th>Raison sociale</th>
				<th>Dirigeant</th>
                <th>Projet</th>
				<th>&nbsp;</th>  
			</tr>
		</thead>
		<tbody>
		<?
		$i = 1;
		foreach($this->lProjects as $c)
		{

			$this->companies->get($c['id_company'],'id_company');
			
			if($this->companies->status_client == 1)
			{
				$this->clients->get($this->companies->id_client_owner,'id_client');
				$dirigeant = $this->clients->prenom.' '.$this->clients->nom;
			}
			else
			{
				$dirigeant = $this->companies->prenom_dirigeant.' '.$this->companies->nom_dirigeant;
			}
			
			?>
			
			<tr class="<?=($i%2 == 1?'':'odd')?> leProject<?=$c['id_project']?>" >
				
				<td><?=$c['id_project']?></td>
				<td><?=$this->companies->name?></td>
				<td><?=$dirigeant?></td>
				<td><?=$c['title_bo']?></td>
				<td align="center">
					<a onClick="attribuer(<?=$c['id_project']?>,<?=$this->id_reception?>);" title="Attribuer au id <?=$c['id_project']?>">Attribuer</a>

				</td>
			</tr>   
		<?
			$i++;
		}
		?>
		</tbody>
	</table>
	
		<table>
			<tr>
				<td id="pager">
					<img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
					<img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
					<input type="text" class="pagedisplay" />
					<img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
					<img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
					<select class="pagesize">
						<option value="5" selected="selected">5</option>
					</select>
				</td>
			</tr>
		</table>
<?
}
else
{
?>
	<p>Il n'y a aucun projet pour le moment.</p>
<?
}
?>
