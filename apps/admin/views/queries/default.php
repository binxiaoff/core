<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{3:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/stats/queries" title="Stats">Stats</a> -</li>
        <li>Requêtes</li>
    </ul>
	<h1>Liste des requêtes</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/queries/add" class="btn_link thickbox">Ajouter une requête</a></div>
	<?
	if(count($this->lRequetes) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Dernière exécution</th>
                    <th>Nombre d'exécutions</th>
                    <th>&nbsp;</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lRequetes as $r)
			{
			?>
                <tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$r['name']?></td>
                    <td><?=($r['executed']!='0000-00-00 00:00:00'?$this->dates->formatDate($r['executed'],'d/m/Y H:i:s'):'Jamais')?></td>
                    <td><?=$r['executions']?></td>
                    <td align="center">
                        <?
                        if(strrchr($r['sql'],'@'))
                        {
                        ?>
                            <a href="<?=$this->lurl?>/queries/params/<?=$r['id_query']?>" class="thickbox">
                                <img src="<?=$this->surl?>/images/admin/modif.png" alt="Renseigner les paramètres" />
                            </a>
                        <?	
                        }
                        else
                        {
                        ?>
                            <a href="<?=$this->lurl?>/queries/execute/<?=$r['id_query']?>" title="Voir le résultat">
                                <img src="<?=$this->surl?>/images/admin/modif.png" alt="Voir le résultat" />
                            </a>
                        <?	
                        }
                        ?> 
                        <a href="<?=$this->lurl?>/queries/edit/<?=$r['id_query']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$r['name']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/queries/delete/<?=$r['id_query']?>" title="Supprimer <?=$r['name']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$r['name']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$r['name']?>" />
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
        <p>Il n'y a aucune requête pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>