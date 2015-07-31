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
        <li><a href="<?=$this->lurl?>/produits" title="Boutique">Boutique</a> -</li>
        <li>Templates Produits</li>
    </ul>
	<h1>Liste des templates produit</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/temproduits/add" class="btn_link thickbox">Ajouter un template produit</a></div>
	<?
	if(count($this->lTemplate) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom du template</th>
                    <th>Fichier</th>
                    <th>&nbsp;</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lTemplate as $t)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$t['name']?></td>
                    <td>
						<?
						if(file_exists($this->path.'apps/default/views/templates/'.$t['slug'].'.php'))
						{
						?>
                        	<?=$t['slug']?>.php 
                            <?
                            if($_SESSION['user']['id_user'] == 1)
							{
							?>
								[<a href="<?=$this->lurl?>/temproduits/edition/<?=$t['slug']?>" title="Editer le fichier">Edition</a>]
                          	<?
							}
							?>
                       	<?
						}
						else
						{
							echo 'Pas de fichier';
						}
						?>
                    </td>
                    <td align="center">
                    	<a href="<?=$this->lurl?>/temproduits/status/<?=$t['id_template']?>/<?=$t['status']?>" title="<?=($t['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                        	<img src="<?=$this->surl?>/images/admin/<?=($t['status']==1?'offline':'online')?>.png" alt="<?=($t['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                       	</a> 
                        <a href="<?=$this->lurl?>/temproduits/edit/<?=$t['id_template']?>" class="thickbox">
                        	<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$t['name']?>" />
                       	</a>
                        <a href="<?=$this->lurl?>/temproduits/elements/<?=$t['id_template']?>" title="Liste des &eacute;l&eacute;ments du template <?=$t['name']?>">
                        	<img src="<?=$this->surl?>/images/admin/database.png" alt="Liste des &eacute;l&eacute;ments du template <?=$t['name']?>" />
                      	</a>
                        <a href="<?=$this->lurl?>/temproduits/delete/<?=$t['id_template']?>" title="Supprimer <?=$t['name']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$t['name']?> ?')">
                        	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$t['name']?>" />
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
        <p>Il n'y a aucun template pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>