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
        <li><a href="<?=$this->lurl?>/tree" title="Edition">Edition</a> -</li>
        <li>Menus</li>
    </ul>
	<h1>Liste des menus</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/menus/add" class="btn_link thickbox">Ajouter un menu</a></div>
    <?
	if(count($this->lMenus) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom du menu</th>
                    <th>Permalink</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lMenus as $m)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$m['nom']?></td>
                    <td><?=$m['slug']?></td>
                    <td align="center">
						<a href="<?=$this->lurl?>/menus/status/<?=$m['id_menu']?>/<?=$m['status']?>" title="<?=($m['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($m['status']==1?'offline':'online')?>.png" alt="<?=($m['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a> 
                        <a href="<?=$this->lurl?>/menus/edit/<?=$m['id_menu']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$m['nom']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/menus/elements/<?=$m['id_menu']?>" title="Elements du menu <?=$m['nom']?>">
                            <img src="<?=$this->surl?>/images/admin/database.png" alt="Elements du menu <?=$m['nom']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/menus/delete/<?=$m['id_menu']?>" title="Supprimer <?=$m['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$m['nom']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$m['nom']?>" />
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
        <p>Il n'y a aucun menu pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>