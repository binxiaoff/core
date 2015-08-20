<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{8:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/preteurs" title="Clients">Prêteurs</a> -</li>
        <li>Gestion des prêteurs</li>
    </ul>
    <?
	if(isset($_POST['form_search_client']))
	{
	?>
    	<h1>Résultats de la recherche prêteurs <?=(count($this->lPreteurs)>0?'('.count($this->lPreteurs).')':'')?></h1>
    <?
	}
	else
	{
	?>
    	<h1>Gestion des prêteurs</h1>
    <?
	}
	?>
     <h2><?=$this->x?> prêteurs au total (dont : <?=$this->y?> "hors ligne" et <?=$this->z?> "sans mouvement")</h2>
    <?
	if(count($this->lPreteurs) > 0)
	{
        ?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>ID</th>
                    <th>Nom / Raison sociale</th>
                    <th>Nom d'usage</th>
                    <th>Prénom / Dirigeant</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Montant sur Unilend</th>
                    <th>Nbre d'enchères en cours</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lPreteurs as $c)
			{

				
				?>
                
            	<tr class="<?=($i%2 == 1?'':'odd')?> " >

                    
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['id_client']?></td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['nom_ou_societe']?></td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['nom_usage']?></td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['prenom_ou_dirigeant']?></td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['email']?></td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['telephone']?></td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=number_format($c['solde'], 2, ',', ' ')?> €</td>
                    <td class="leLender<?=$c['id_lender_account']?>"><?=$c['bids_encours']?></td>
                    <td align="center">
                    	<?
						if($c['novalid'] == 1)
						{
							?>
							
								<img onclick="if(confirm('Voulez vous supprimer définitivement ce preteur ?')){window.location = '<?=$this->lurl?>/preteurs/gestion/delete/<?=$c['id_client']?>/';}" src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer" style="cursor:pointer;" />
                                <a href="<?=$this->lurl?>/preteurs/edit/<?=$c['id_lender_account']?>">
								<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['nom'].' '.$c['prenom']?>" />
							</a>
							
                            <?
						}
						else
						{
							?>
							
								<img onclick="if(confirm('Voulez vous <?=($c['status']==1?'Passer hors ligne':'Passer en ligne')?> ce preteur ?')){window.location = '<?=$this->lurl?>/preteurs/gestion/status/<?=$c['id_client']?>/<?=$c['status']?>';}" src="<?=$this->surl?>/images/admin/<?=($c['status']==1?'offline':'online')?>.png" alt="<?=($c['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
							
							<a href="<?=$this->lurl?>/preteurs/edit/<?=$c['id_lender_account']?>">
								<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['nom'].' '.$c['prenom']?>" />
							</a>
                            <script>
							$(".leLender<?=$c['id_lender_account']?>").click(function() {
								$(location).attr('href','<?=$this->lurl?>/preteurs/edit/<?=$c['id_lender_account']?>');
							});
							</script>
							<?
						}
						?>
                        
                  	</td>
                </tr>   
            <?
				$i++;
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
     	<?
		if(isset($_POST['form_search_client']))
		{
		?>
			<p>Il n'y a aucun prêteur pour cette recherche.</p>
		<?
		}
		else
		{
		?>
			<p>Il n'y a aucun prêteur pour le moment.</p>
		<?
		}
		?>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>