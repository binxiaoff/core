<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{6:{sorter: false}}});	
		<?
		if($this->nb_lignes != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});
			$(".tsbis").tablesorterPager({container: $("#pager2"),positionFixed: false,size: <?=$this->nb_lignes?>});		
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
        <li><a href="<?=$this->lurl?>/produits" title="Produits">Produits</a> -</li>
        <li>Avis des produits</li>
    </ul>
	<h1>Liste des avis en attente de modération</h1>
    <?php
	if(count($this->lAvis) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Date</th>
                    <th>Produit</th>
                    <th>Nom</th>
                    <th>IP</th>
                    <th>Vote</th>
                    <th>Avis</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?php
			$i = 1;
			foreach($this->lAvis as $a)
			{
				// On recupere les infos du produit
				$this->p = $this->produits->detailsProduit($a['id_produit'],$this->language);
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                	<td><?=$this->dates->formatDate($a['added'],'d/m/Y H:i:s')?></td>
                    <td><?=$this->p['nom']?></td>
                    <td><?=$a['nom'].' '.$a['prenom']?><br/>(<?=$a['email']?>)</td>
                    <td><?=$a['ip']?></td>
                    <td align="center"><?=$a['note']?></td>
                    <td><?=($a['titre'] != ''?$a['titre'].' - ':'').$a['avis']?></td>
                    <td align="center">
                    	<a href="<?=$this->lurl?>/produits/avis/valide/<?=$a['id_avis']?>" title="Valider l'avis">
                        	<img src="<?=$this->surl?>/images/admin/check.png" alt="Valider l'avis" />
                       	</a>
                        <a href="<?=$this->lurl?>/produits/avis/delete/<?=$a['id_avis']?>" title="Supprimer l'avis" onclick="return confirm('Etes vous sur de vouloir supprimer l\'avis ?')">
                        	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer l'avis" />
                       	</a>
                	</td>
                </tr>   
            <?php	
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
        <p>Il n'y a aucun avis à modéré pour le moment.</p>
    <?
    }
    ?>
    <br /><br />
    <h1>Liste des avis sur les produits</h1>
    <?php
	if(count($this->lAvisOK) > 0)
	{
	?>
    	<table class="tablesorter tsbis">
        	<thead>
                <tr>
                    <th>Date</th>
                    <th>Produit</th>
                    <th>Nom</th>
                    <th>IP</th>
                    <th>Vote</th>
                    <th>Avis</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?php
			$i = 1;
			foreach($this->lAvisOK as $a)
			{
				// On recupere les infos du produit
				$this->p = $this->produits->detailsProduit($a['id_produit'],$this->language);
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                	<td><?=$this->dates->formatDate($a['added'],'d/m/Y H:i:s')?></td>
                    <td><?=$this->p['nom']?></td>
                    <td><?=$a['nom'].' '.$a['prenom']?><br/>(<?=$a['email']?>)</td>
                    <td><?=$a['ip']?></td>
                    <td align="center"><?=$a['note']?></td>
                    <td><?=($a['titre'] != ''?$a['titre'].' - ':'').$a['avis']?></td>
                    <td align="center">
                    	<a href="<?=$this->lurl?>/produits/avis/delete/<?=$a['id_avis']?>" title="Supprimer l'avis" onclick="return confirm('Etes vous sur de vouloir supprimer l\'avis ?')">
                        	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer l'avis" />
                       	</a>
                	</td>
                </tr>   
            <?php	
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
        <p>Il n'y a aucun avis pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>