<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{7:{sorter: false}}});
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
        <li>Campagnes</li>
    </ul>
	<h1>Liste des campagnes</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/partenaires/add" class="btn_link thickbox">Ajouter une campagne</a></div>
    <?
	if(count($this->lPartenaires) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Lien</th>
                    <th>Type</th>
                    <?php /*?><th>Code promo</th><?php */?>
                    <th>Nb de clics</th>
                    <th>Nb Cmdes</th>
                    <th>CA (TTC)</th>
                    <th>&nbsp;</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lPartenaires as $p)
			{
				// Recuperation des infos
				$this->promotions->get($p['id_code'],'id_code');
				$this->partenaires_types->get($p['id_type'],'id_type');

				// Recuperation du CA
				$capart = $this->partenaires->recupCA($p['id_partenaire']);
				$nbcmd = $this->partenaires->recupCmde($p['id_partenaire']);

				// Recuperation du nombre de clic total
				$nbclic = $this->partenaires->nbClicTotal($p['id_partenaire']);
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$p['nom']?></td>
                    <td>/p/<?=$p['hash']?>/</td>
                    <td><?=$this->partenaires_types->nom?></td>
                    <?php /*?><td><?=$this->promotions->code?></td><?php */?>
                    <td><?=$nbclic?></td>
                    <td><?=$nbcmd?></td>
                    <td><?=$this->ficelle->formatNumber($capart)?>&nbsp;&euro;</td>
                    <td align="center">
						<a href="<?=$this->lurl?>/partenaires/status/<?=$p['id_partenaire']?>/<?=$p['status']?>" title="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            <img src="<?=$this->surl?>/images/admin/<?=($p['status']==1?'offline':'online')?>.png" alt="<?=($p['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                        </a>
                        <a href="<?=$this->lurl?>/partenaires/edit/<?=$p['id_partenaire']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$p['nom']?>" />
                        </a>
                        <form method="post" id="formQuery<?=$p['id_partenaire']?>" action="<?=$this->lurl?>/queries/excel/1" target="_blank" style="margin:0; padding:0; border:0; display:inline;">
							<input type="hidden" name="param_ID_Partenaire" value="<?=$p['id_partenaire']?>"/>
                        </form>
                        <a onclick="document.getElementById('formQuery<?=$p['id_partenaire']?>').submit();return false;" title="Exporter les commandes de la campagne">
                            <img src="<?=$this->surl?>/images/admin/xls.png" alt="Exporter les commandes de la campagne" />
                        </a>
                        <a href="<?=$this->lurl?>/partenaires/delete/<?=$p['id_partenaire']?>" title="Supprimer <?=$p['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$p['nom']?> ?')">
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
        <p>Il n'y a aucune campagne pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>