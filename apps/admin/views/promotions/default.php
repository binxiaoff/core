<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{6:{sorter: false}}});	
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
	$(document).ready(function(){
		$(".lightbox").colorbox({
			onComplete:function(){
				$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
				$("#datepik_from").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
				$("#datepik_to").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
			}
		});
	});
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/produits" title="Boutique">Boutique</a> -</li>
        <li>Promotions</li>
    </ul>
	<h1>Liste des codes promo</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/promotions/add" class="btn_link lightbox">Ajouter un code promo</a></div>
    <?
	if(count($this->lCodes) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                	<th>Code</th>
                    <th>Du</th>
                    <th>Au</th>
                    <th>Valeur</th>
                    <th>FDP</th>
                    <th>Seuil</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lCodes as $c)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$c['code']?></td>
                    <td><?=$this->dates->formatDate($c['from'],'d/m/Y')?></td>
                    <td><?=$this->dates->formatDate($c['to'],'d/m/Y')?></td>
                    <td><?=$c['value']?></td>
                    <td><?=($c['fdp']==1?'Offert':'Payant')?></td>
                    <td><?=$c['seuil']?></td>
                    <td align="center">
						<?
                        if($c['status'] < 2)
                        {
                        ?>
                        	<a href="<?=$this->lurl?>/promotions/status/<?=$c['id_code']?>/<?=$c['status']?>" title="<?=($c['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                                <img src="<?=$this->surl?>/images/admin/<?=($c['status']==1?'offline':'online')?>.png" alt="<?=($c['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                            </a> 
                            <a href="<?=$this->lurl?>/promotions/edit/<?=$c['id_code']?>" class="lightbox">
                                <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['code']?>" />
                            </a>
                            <a href="<?=$this->lurl?>/promotions/delete/<?=$c['id_code']?>" title="Supprimer <?=$c['code']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$c['code']?> ?')">
                                <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$c['code']?>" />
                            </a>
                      	<?
                        }
                        else
                        {
                        ?>
							<a href="<?=$this->lurl?>/promotions/edit/<?=$c['id_code']?>" class="lightbox">
                                <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['code']?>" />
                            </a>
                            <?php
							if($c['status'] == 3)
							{
							?>
                            	<a href="<?=$this->lurl?>/promotions/delete/<?=$c['id_code']?>" title="Supprimer <?=$c['code']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$c['code']?> ?')">
                                    <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$c['code']?>" />
                                </a>
                            <?php	
							}
							?>
						<?	
                        }
                        ?>
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
        <p>Il n'y a aucune promotions pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>