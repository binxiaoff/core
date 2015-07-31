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
        <li>Gestion des marques</li>
    </ul>
	<h1>Liste des marques</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/brands/add" class="btn_link thickbox">Ajouter une marque</a></div>
    <?
	if(count($this->lBrands) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Name</th>
                    <th>Image</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lBrands as $b)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$b['name']?></td>
                    <td>
						<?
                        if($b['image'] != '')
                        {
                            list($width,$height) = @getimagesize($this->surl.'/var/images/marques/'.$b['image']);
                        ?>   
                            <a href="<?=$this->surl?>/var/images/marques/<?=$b['image']?>" class="thickbox">
                                <img src="<?=$this->surl?>/var/images/marques/<?=$b['image']?>" alt="<?=$b['name']?>"<?=($height > 80?' height="80"':($width > 150?' width="150"':''))?> />
                            </a>
                        <?
                        }
                        else
                        {
                        ?>
                            &nbsp;
                        <?	
                        }
                        ?>
                    </td>
                    <td align="center">
						<a href="<?=$this->lurl?>/brands/status/<?=$b['id_brand']?>/<?=$b['status']?>" title="<?=($b['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($b['status']==1?'offline':'online')?>.png" alt="<?=($b['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a> 
                        <a href="<?=$this->lurl?>/brands/edit/<?=$b['id_brand']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$b['name']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/brands/delete/<?=$b['id_brand']?>" title="Supprimer <?=$b['name']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$b['name']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$b['name']?>" />
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
        <p>Il n'y a aucune marque pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>