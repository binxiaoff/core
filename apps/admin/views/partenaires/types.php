<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{1:{sorter: false}}});	
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
        <li>Types de campagnes</li>
    </ul>
	<h1>Liste des types de campagnes</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/partenaires/addType" class="btn_link thickbox">Ajouter un type de campagne</a></div>
    <?
	if(count($this->lTypes) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Type de campagne</th>
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
                    <td align="center">
						<a href="<?=$this->lurl?>/partenaires/types/status/<?=$t['id_type']?>/<?=$t['status']?>" title="<?=($t['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($t['status']==1?'offline':'online')?>.png" alt="<?=($t['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a> 
                        <a href="<?=$this->lurl?>/partenaires/editType/<?=$t['id_type']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$t['nom']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/partenaires/types/delete/<?=$t['id_type']?>" title="Supprimer <?=$t['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$t['nom']?> ?')">
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
        <p>Il n'y a aucun type de campagne pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>