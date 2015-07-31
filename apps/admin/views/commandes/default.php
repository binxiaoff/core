<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{7:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/commandes" title="Commandes">Commandes</a> -</li>
        <li>Commandes reçues</li>
    </ul>
	<h1>Liste des commandes en cours de traitement</h1>
    <?
	if(count($this->lCommandes) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th align="center">Date</th>
                    <th align="center">Référence</th>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Livraison</th>
                    <th align="center">Montant</th>
                    <th align="center">Statut</th>
                    <th align="center">&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lCommandes as $cmd)
			{
				// Recuperation des infos clients
				$this->clients->get($cmd['id_client']);
				
				// Eteblissement du statut
				switch($cmd['etat'])
				{
					case 0:
						$etat = 'En attente';
					break;							
					case 1:
						$etat = 'Validée';
					break;
					case 2:
						$etat = 'Expédiée';
					break;
					case 3:
						$etat = 'Annulée';
					break;
				}
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td align="center"><?=$this->dates->formatDate($cmd['date_transaction'],'d/m/Y')?></td>
                    <td align="center"><?=$cmd['id_transaction']?></td>
                    <td><?=$this->clients->civilite?> <?=$this->clients->prenom?> <?=$this->clients->nom?></td>
                    <td><?=$this->clients->email?></td>
                    <td><?=$cmd['cp_liv'].' '.$cmd['ville_liv']?></td>
                    <td align="center"><?=number_format($cmd['montant']/100,2,',',' ')?> €</td>
                    <td align="center"><?=$etat?></td>
                    <td align="center">
                    	<a href="<?=$this->lurl?>/commandes/details/<?=$cmd['id_transaction']?>" class="thickbox">
                        	<img src="<?=$this->surl?>/images/admin/modif.png" alt="Voir le détail de la commande" title="Voir le détail de la commande" />
                      	</a>
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
        <p>Il n'y a aucune commande pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>