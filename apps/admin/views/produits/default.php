<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{5:{sorter: false}}});	
		<?
		if($this->nb_lignes != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});	
			$(".tab1").tablesorterPager({container: $("#pager1"),positionFixed: false,size: <?=$this->nb_lignes?>});	
			$(".tab2").tablesorterPager({container: $("#pager2"),positionFixed: false,size: <?=$this->nb_lignes?>});		
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
        <li>Produits</li>
    </ul>
	<h1>Liste des produits (<?=count($this->lTypeProduit)?> produit<?=(count($this->lTypeProduit)>1?'s':'')?> au total)</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/produits/add/Produit" class="btn_link" title="Ajouter un produit">Ajouter un produit</a>&nbsp;&nbsp;<a href="<?=$this->lurl?>/produits/recherche" class="btn_link thickbox">Rechercher un produit</a></div>
    <?
	if(count($this->lTypeProduit) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Référence</th>
                    <th>Nom</th>
                    <th align="center">Prix</th>
                    <th align="center">Stock</th>
                    <th>Catégorie</th>
                    <th>&nbsp;</th>  
                </tr>
         	</thead>
            <tbody>
            <?
			$i = 1;
            foreach($this->lTypeProduit as $p)
            {
			?>
				<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$p['reference']?></td>
                    <td><?=$p['nom']?></td>
                    <td align="center"><?=number_format($p['prix'],2,',','')?> &euro;</td>
                    <td align="center"><?=$p['stock']?></td>
                    <td><?=$p['categorie']?></td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/produits/status/<?=$p['id_produit']?>/<?=$p['status']?>" title="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($p['status']==1?'offline':'online')?>.png" alt="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a>
                        <a href="<?=$this->lurl?>/produits/edit/<?=$p['id_produit']?>/Produit" title="Modifier <?=$p['reference']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$p['reference']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/produits/delete/<?=$p['id_produit']?>" title="Supprimer <?=$p['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$p['nom']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$p['nom']?>" />
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
        <p>Il n'y a aucun produit pour le moment.</p>
    <?
    }
    ?>
    <br>
    <h1>Liste des échantillons (<?=count($this->lTypeEchantillon)?> échantillon<?=(count($this->lTypeEchantillon)>1?'s':'')?> au total)</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/produits/add/Echantillon" class="btn_link" title="Ajouter un échantillon">Ajouter un échantillon</a></div>
    <?
	if(count($this->lTypeEchantillon) > 0)
	{
	?>
    	<table class="tablesorter tab1">
        	<thead>
                <tr>
                    <th>Référence</th>
                    <th>Nom</th>
                    <th align="center">Prix</th>
                    <th align="center">Stock</th>
                    <th>Catégorie</th>
                    <th>&nbsp;</th>  
                </tr>
         	</thead>
            <tbody>
            <?
			$i = 1;
            foreach($this->lTypeEchantillon as $p)
            {
			?>
				<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$p['reference']?></td>
                    <td><?=$p['nom']?></td>
                    <td align="center"><?=number_format($p['prix'],2,',','')?> &euro;</td>
                    <td align="center"><?=$p['stock']?></td>
                    <td><?=$p['categorie']?></td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/produits/status/<?=$p['id_produit']?>/<?=$p['status']?>" title="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($p['status']==1?'offline':'online')?>.png" alt="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a>
                        <a href="<?=$this->lurl?>/produits/edit/<?=$p['id_produit']?>/Echantillon" title="Modifier <?=$p['reference']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$p['reference']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/produits/delete/<?=$p['id_produit']?>" title="Supprimer <?=$p['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$p['nom']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$p['nom']?>" />
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
                    <td id="pager1">
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
        <p>Il n'y a aucun échantillon pour le moment.</p>
    <?
    }
    ?>
    <br>
    <h1>Liste des cadeaux (<?=count($this->lTypeCadeau)?> cadeau<?=(count($this->lTypeCadeau)>1?'x':'')?> au total)</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/produits/add/Cadeau" class="btn_link" title="Ajouter un cadeau">Ajouter un cadeau</a></div>
    <?
	if(count($this->lTypeCadeau) > 0)
	{
	?>
    	<table class="tablesorter tab2">
        	<thead>
                <tr>
                    <th>Référence</th>
                    <th>Nom</th>
                    <th align="center">Prix</th>
                    <th align="center">Stock</th>
                    <th>Catégorie</th>
                    <th>&nbsp;</th>  
                </tr>
         	</thead>
            <tbody>
            <?
			$i = 1;
            foreach($this->lTypeCadeau as $p)
            {
			?>
				<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$p['reference']?></td>
                    <td><?=$p['nom']?></td>
                    <td align="center"><?=number_format($p['prix'],2,',','')?> &euro;</td>
                    <td align="center"><?=$p['stock']?></td>
                    <td><?=$p['categorie']?></td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/produits/status/<?=$p['id_produit']?>/<?=$p['status']?>" title="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($p['status']==1?'offline':'online')?>.png" alt="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a>
                        <a href="<?=$this->lurl?>/produits/edit/<?=$p['id_produit']?>/Cadeau" title="Modifier <?=$p['reference']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$p['reference']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/produits/delete/<?=$p['id_produit']?>" title="Supprimer <?=$p['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$p['nom']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$p['nom']?>" />
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
                    <td id="pager2">
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
        <p>Il n'y a aucun cadeau pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>