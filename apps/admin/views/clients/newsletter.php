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
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/clients" title="Clients">Clients</a> -</li>
        <li>Newsletter</li>
    </ul>
	<h1>Liste des <?=count($this->lInscrits)?> derniers inscrits à la newsletter</h1>    
    <?
	if(count($this->lInscrits) > 0)
	{
	?>
    	<div class="btnDroite"><a href="<?=$this->lurl?>/clients/exportNews" class="btn_link">Exporter les inscrits</a></div>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Langue</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lInscrits as $g)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$g['nom']?></td>
                    <td><?=$g['prenom']?></td>
                    <td><?=$g['email']?></td>
                    <td><?=$g['id_langue']?></td>
                    <td align="center">
                    	<?
						if($g['status'] == 0)
						{
						?>                   
                            <a href="<?=$this->lurl?>/clients/newsletter/join/<?=$g['id_newsletter']?>" title="Ajouter <?=$g['nom'].' '.$g['prenom']?>" onclick="return confirm('Etes vous sur de vouloir abonner <?=$g['nom'].' '.$g['prenom']?> ?')">
                                <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$g['nom'].' '.$g['prenom']?>" />
                            </a>
                      	<?
						}
						else
						{
						?>
                        	<a href="<?=$this->lurl?>/clients/newsletter/quit/<?=$g['id_newsletter']?>" title="Supprimer <?=$g['nom'].' '.$g['prenom']?>" onclick="return confirm('Etes vous sur de vouloir desabonner <?=$g['nom'].' '.$g['prenom']?> ?')">
                            	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$g['nom'].' '.$g['prenom']?>" />
                        	</a>
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
    	<p>Il n'y a aucun inscrit pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>