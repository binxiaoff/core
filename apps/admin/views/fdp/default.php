<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{5:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/produits" title="Boutique">Boutique</a> -</li>
        <li>Frais de port</li>
    </ul>
	<h1>Gestion des frais de port</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/fdp/addZone" class="btn_link thickbox">Ajouter une zone et un premier tarif</a><br /><br /></div>
    <?
	if(count($this->lZones) > 0)
	{
		foreach($this->lZones as $z)
		{
		?>
        	<h2>Détails de la Zone <?=$z['id_zone']?> <a href="<?=$this->lurl?>/fdp/deleteZone/<?=$z['id_zone']?>" title="Supprimer la zone <?=$z['id_zone']?> et tous les FDP" onclick="return confirm('Etes vous sur de vouloir supprimer la zone <?=$z['id_zone']?> et tous les FDP ?')"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer la zone <?=$z['id_zone']?> et tous les FDP" /></a></h2>
            <div class="btnDroite">
            	<a href="<?=$this->lurl?>/fdp/editZone/<?=$z['id_zone']?>" class="btn_link thickbox">Modifier la zone <?=$z['id_zone']?></a>&nbsp;&nbsp;
                <a href="<?=$this->lurl?>/fdp/addFDP/<?=$z['id_zone']?>" class="btn_link thickbox">Ajouter un montant à la zone <?=$z['id_zone']?></a>
          	</div>
            <?
			if(count($this->fdp->recupMontantZone($z['id_zone'],$this->language)) > 0)
			{
			?>
                <table class="tablesorter">
                    <thead>
                        <tr>
                            <th>Poids (g)</th>
                            <th>Montant</th>
                            <th>Montant réduit</th>
                            <th>Type</th>
                            <th>Gratuit à partir de</th>
                            <th>&nbsp;</th>  
                        </tr>
                    </thead>
                    <tbody>
                    <?
                    $i = 1;
                    foreach($this->fdp->recupMontantZone($z['id_zone'],$this->language) as $fdp)
                    {
                    ?>
                        <tr<?=($i%2 == 1?'':' class="odd"')?>>
                            <td><?=$fdp['poids']?></td>
                            <td><?=$fdp['fdp']?></td>
                            <td><?=$fdp['fdp_reduit']?></td>
                            <td><?=$fdp['type']?></td>
                            <td><?=$fdp['montant_free']?></td>
                            <td align="center">
                                <a href="<?=$this->lurl?>/fdp/editFDP/<?=$fdp['id_fdp']?>" class="thickbox">
                                    <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier le FDP" />
                                </a>
                                <a href="<?=$this->lurl?>/fdp/delete/<?=$fdp['id_fdp']?>" title="Supprimer le FDP" onclick="return confirm('Etes vous sur de vouloir supprimer le FDP ?')">
                                    <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer le FDP" />
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
			}
			else
			{
			?>
            	<p>Il n'y a aucun FDP pour la zone.</p>
            <?
			}
			?>
            <br /><br />
   		<?
		}
		?>
	<?
    }
    else
    {
    ?>
        <p>Il n'y a aucune zone pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>