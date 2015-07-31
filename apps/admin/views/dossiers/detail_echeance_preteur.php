<script type="text/javascript">
	$(document).ready(function(){
		
		jQuery.tablesorter.addParser({ id: "fancyNumber", is: function(s) { return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s); }, format: function(s) { return jQuery.tablesorter.formatFloat( s.replace(/,/g,'').replace(' €','').replace(' ','') ); }, type: "numeric" });
		
		$(".tablesorter").tablesorter({headers:{9:{sorter: false}}});	
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
	unset($_SESSION['freeow']);
	}
	?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/dossiers" title="Dossiers">Dossiers</a> -</li>
        <li><a href="<?=$this->lurl?>/dossiers/remboursements" title="Remboursements">Remboursements</a> -</li>
        <li><a href="<?=$this->lurl?>/dossiers/detail_remb/<?=$this->params[0]?>" title="Detail remboursements">Detail remboursements</a> -</li>
        <li><a href="<?=$this->lurl?>/dossiers/detail_remb_preteur/<?=$this->params[0]?>" title="Detail prêteur">Detail prêteur</a> -</li>
        <li>Detaile échéance prêteur</li>
    </ul>
  	
    
    
	<h1>Liste des <?=count($this->lRemb)?> derniers remboursements</h1>
	
    <?
	if(count($this->lRemb) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr> 
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Montant</th>
                    
                    <th>Prélèvements<br />obligatoires</th>
                    <th>Retenues à la<br />source</th>
                    <th>CSG</th>
                    <th>Prélèvements<br />sociaux</th>
                    <th>Contributions<br />additionnelles</th>
                    <th>Prélèvements <br />de solidarité</th>
                    <th>CRDS</th>
                    
					<th>capital</th>
                  	<th>interets</th>
                    <th>commission</th>
                    <th>tva</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;

			foreach($this->lRemb as $r)
			{
				$this->projects->get($r['id_project'],'id_project');
				$this->lenders_accounts->get($r['id_lender'],'id_lender_account');
				
				$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                	
                    <td><?=$this->clients->nom?></td>
                    <td><?=$this->clients->prenom?></td>
                    <td><?=number_format($r['montant']/100,2,',',' ')?></td>
                    
                    <td><?=number_format($r['prelevements_obligatoires'],2,',',' ')?></td>
                    <td><?=number_format($r['retenues_source'],2,',',' ')?></td>
                    <td><?=number_format($r['csg'],2,',',' ')?></td>
                    <td><?=number_format($r['prelevements_sociaux'],2,',',' ')?></td>
                    <td><?=number_format($r['contributions_additionnelles'],2,',',' ')?></td>
                    <td><?=number_format($r['prelevements_solidarite'],2,',',' ')?></td>
                    <td><?=number_format($r['crds'],2,',',' ')?></td>
                    
                    <td><?=number_format($r['capital']/100,2,',',' ')?></td>
                    <td><?=number_format($r['interets']/100,2,',',' ')?></td>
                    <td><?=number_format($r['commission']/100,2,',',' ')?></td>
                    <td><?=number_format($r['tva']/100,2,',',' ')?></td>
                    <td><?=$this->dates->formatDate($r['date_echeance'],'d/m/Y')?></td>
                    <td><?=($r['status']==1?'Remboursé':'A venir')?></td>
                </tr>   
            <?
				$i++;
            }
			// ajout de la ligne du RA
            if($this->montant_ra > 0)
            {
                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>

                    <td><?= $this->clients->nom ?></td>
                    <td><?= $this->clients->prenom ?></td>
                    <td><?= number_format($this->montant_ra , 2, ',', ' ') ?></td>

                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>

                    <td><?= number_format($this->montant_ra , 2, ',', ' ') ?></td>
                    <td>0</td>
                    <?php /* ?><td><?=number_format($r['commission']/100,2,',',' ')?></td>
                      <td><?=number_format($r['tva']/100,2,',',' ')?></td><?php */ ?>
                    <td><?= $this->dates->formatDate($this->date_ra, 'd/m/Y') ?></td>
                    <td><?= ($r['status'] == 1 ? 'Remboursé' : 'A venir') ?></td>
                </tr> 
                <?php
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
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>