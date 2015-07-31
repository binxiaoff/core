<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{2:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/clients" title="Clients">Clients</a> -</li>
        <li>Groupes de clients</li>
    </ul>
	<h1>Liste des groupes de clients</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/clients/addGroupe" class="btn_link thickbox">Ajouter un groupe</a></div>
    <?
	if(count($this->lGroupes) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom du groupe</th>
                    <th>Nombre de clients</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lGroupes as $g)
			{
				$nb = $this->clients_groupes->counter('id_groupe = '.$g['id_groupe']);
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$g['nom']?></td>
                    <td><?=$nb?></td>
                    <td align="center">
						<a href="<?=$this->lurl?>/clients/editGroupe/<?=$g['id_groupe']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$g['nom']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/clients/detailsGroupe/<?=$g['id_groupe']?>" title="Details du groupe <?=$g['nom']?>">
                            <img src="<?=$this->surl?>/images/admin/modif.png" alt="Details du groupe <?=$g['nom']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/clients/groupes/delete/<?=$g['id_groupe']?>" title="Supprimer <?=$g['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$g['nom']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$g['nom']?>" />
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
    	<p>Il n'y a aucun groupe pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>