<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{4:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li><a href="<?=$this->lurl?>/mails" title="Mails">Mails</a> -</li>
        <li>Historique des Mails</li>
    </ul>
	<h1>Historique des emails (<?=$this->nbMails?> résultat<?=($this->nbMails>1?'s':'')?>)</h1>
    <div class="btnDroite">
    	<a href="<?=$this->lurl?>/mails/recherche" class="btn_link lightbox">Rechercher</a>
        <?php /*?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="<?=$this->lurl?>/mails/purge" onClick="if(confirm('Êtes vous certain ?')){ return true; } else { return false; }" class="btnRouge">Vider l'historique</a><?php */?>
  	</div>
    <?
	if(count($this->lMails) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Sujet</th>                   
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lMails as $m)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                	<td><?=$this->dates->formatDate($m['added'],'d/m/Y H:i')?></td>
                    <td><?=$m['from']?></td>
                    <td><?=($m['email_nmp']!=''?$m['email_nmp']:$m['to'])?></td>
                    <td><?=$m['subject']?></td>
                    <td align="center">
                    	<a href="<?=$this->url?>/mails/logsdetails/<?=$m['id_filermails']?>" class="thickbox">
                        	<img src="<?=$this->surl?>/images/admin/modif.png" alt="Voir <?=$m['subject']?>" />
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
        <p>Il n'y a aucun email pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>