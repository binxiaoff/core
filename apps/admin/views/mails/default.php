<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{5:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Mails</li>
    </ul>
	<h1>Liste des emails du site</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/mails/add" class="btn_link" title="Ajouter un email">Ajouter un email</a></div>
    <?
	if(count($this->lMails) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Type</th>
                    <th>Nom</th>
                    <th>Expéditeur</th>
                    <th>Sujet</th>
                    <th>Mise à jour</th>                    
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
                    <td><?=$m['type']?></td>
                    <td><?=$m['name']?></td>
                    <td><?=$m['exp_name']?><?=($m['exp_email']!=''?' &lt;'.$m['exp_email'].'&gt;':'')?></td>
                    <td><?=$m['subject']?></td>
                    <td><?=$this->dates->formatDate($m['updated'],'d/m/Y H:i')?></td>
                    <td align="center">
						<a href="<?=$this->lurl?>/mails/edit/<?=$m['type']?>" title="Modifier <?=$m['name']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$m['name']?>" />
                        </a>
                        <a href="<?=$this->lurl?>/mails/delete/<?=$m['type']?>" title="Supprimer <?=$m['name']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$m['name']?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$m['name']?>" />
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