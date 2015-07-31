<script type="text/javascript">
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
        <li>Types de frais de port</li>
    </ul>
	<h1>Liste des types de frais de port</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/fdp/addType" class="btn_link" title="Ajouter un type de FDP">Ajouter un type de FDP</a></div>
    <?
	if(count($this->lTypes) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Affichage</th>
                    <th>Description</th>
                    <th>Delais Min.</th>
                    <th>Delais Max.</th>
                    <th>Position</th>                   
                    <th>&nbsp;</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lTypes as $t)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$t['nom']?></td>
                    <td><?=$t['affichage']?></td>
                    <td><?=$t['description']?></td>
                    <td><?=$t['delais_min']?></td>
                    <td><?=$t['delais_max']?></td>
                    <td align="center">
                    	<?
						if(count($this->lTypes) > 1)
						{
							if($t['ordre'] > 0)
							{
							?>
								<a href="<?=$this->lurl?>/fdp/types/up/<?=$t['id_type']?>" title="Remonter">
                                	<img src="<?=$this->surl?>/images/admin/up.png" alt="Remonter" />
                               	</a> 
							<?
							}							
							if($t['ordre'] < (count($this->lTypes)-1))
							{
							?>
								<a href="<?=$this->lurl?>/fdp/types/down/<?=$t['id_type']?>" title="Descendre">
                                	<img src="<?=$this->surl?>/images/admin/down.png" alt="Descendre" />
                               	</a>
							<?
							}
						}
						?>
                    </td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/fdp/types/status/<?=$t['id_type']?>/<?=$t['status']?>" title="<?=($t['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                        	<img src="<?=$this->surl?>/images/admin/<?=($t['status']==1?'offline':'online')?>.png" alt="<?=($t['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                       	</a> 
                        <a href="<?=$this->lurl?>/fdp/editType/<?=$t['id_type']?>" title="Modifier <?=$t['nom']?>">
                        	<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$t['nom']?>" />
                       	</a>
                        <a href="<?=$this->lurl?>/fdp/types/delete/<?=$t['id_type']?>" title="Supprimer <?=$t['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$t['nom']?> ?')">
                        	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$t['nom']?>" />
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
        <p>Il n'y a aucun type de frais de port pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>