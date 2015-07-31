<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter();	
		<?
		if($this->nb_lignes != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});		
		<?
		}
		?>

	});
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
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Administrateurs</li>
    </ul>
	<h1>Requete dossiers</h1>
    
    <div style="margin-bottom:20px; float:right;"><a href="<?=$this->lurl?>/stats/requete_dossiers_csv" class="btn_link">Recuperation du CSV</a></div>
    <?
	if(count($this->lEmpr) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                	<th>Cdos</th>
                    <th>Dénomination</th>
                    <th>Adresse</th>
                    <th>Voie</th>   
                    <th>CodeCommune</th>                     
                    <th>commune</th>
                    <th>CodePostal</th>   
                    <th>Ville</th>
                    <th>Activités</th>
                    <th>Siret</th>
                    <th>APE</th>
                    <th>F Juridique</th>
                    <th>Capital</th>
                    <th>CapitalMonnaie</th>
                    <th>LieuRCS</th>
                    <th>Responsable</th>
                    <th>Fonction</th>
                    <th>Téléphone</th>
                    <th>Fax</th>
                    <th>CatJuridique</th>
                    <th>CDéclaration</th>
                    <th>Cbénéficiaire</th>    
                </tr>
           	</thead>           	
            <tbody>
            <?
            $i = 1;
            foreach($this->lEmpr as $e)
            {	

				$this->companies->get($e['id_client'],'id_client_owner');
				
				$statutRemb = false;
				$lPorjects = $this->projects->select('id_company = '.$this->companies->id_company);
				if($lPorjects != false){
					foreach($lPorjects as $p){
						$this->projects_status->getLastStatut($p['id_project']);
						if($this->projects_status->status == 80){
							$statutRemb = true;	
						}
					}
				}
				
				if($statutRemb == true){
				
					$this->clients_adresses->get($e['id_client'],'id_client');
					
					$this->insee->get(str_replace(' ','-',trim($this->clients_adresses->ville)),'NCCENR');
					
					// Code commune insee
					$dep = str_pad($this->insee->DEP,2,'0', STR_PAD_LEFT);
					$com = str_pad($this->insee->COM,3,'0', STR_PAD_LEFT);
					$codeCom = $dep.$com;
	
					$pos = strpos(str_replace('.','',$this->companies->rcs), 'RCS');
					$pos +=3;
					$lieuRCS = trim(substr($this->companies->rcs,$pos));
					
					?>
					<tr<?=($i%2 == 1?'':' class="odd"')?>>
						<td><?=$e['id_client']?></td>
						<td><?=$this->companies->name?></td>
						<td></td>
						<td><?=$this->clients_adresses->adresse1?></td>
						<td><?=$codeCom?></td>
						<td></td>
						<td><?=$this->clients_adresses->cp?></td>
						<td><?=$this->clients_adresses->ville?></td>
						<td><?=$this->companies->activite?></td>
						<td><?=$this->companies->siret?></td>
						<td></td>
						<td><?=$this->companies->forme?></td>
						<td><?=$this->companies->capital?></td>
						<td>"EUR"</td>
						<td><?=$lieuRCS?></td>
						<td><?=$e['prenom'].' '.$e['nom']?></td>
						<td><?=$e['fonction']?></td>
						<td><?=$e['telephone']?></td>
						<td></td>
						<td></td>
						<td>C</td>
						<td>B</td>
					</tr>   
					<?	
					$i++;
				}
            }
            ?>
            </tbody>
        </table>
        <?
		if($this->nb_lignes != '')
		{
		?>
			<table>
                <tr>
                    <td id="pager">
                        <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                        	<option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                       	</select>
                    </td>
                </tr>
            </table>
		<?
		}
		?>
  	<?
	}
	else
	{
	?>
    	<p>Il n'y a aucun dossier pour le moment.</p>
    <?
	}
	?>
</div>
<?php unset($_SESSION['freeow']); ?>