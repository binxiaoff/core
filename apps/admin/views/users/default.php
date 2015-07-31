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
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Administrateurs</li>
    </ul>
	<h1>Gestion des utilisateurs</h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/users/add" class="btn_link thickbox">Ajouter un utilisateur</a></div>
    <?
	if(count($this->lUsers) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Pr&eacute;nom</th>
                    <th>E-mail</th>   
                    <th>Droits</th>   
                    <th>Ajouter</th>
                    <th>Mise à jour</th>
                    <th>Dernière connexion</th>
                    <th>&nbsp;</th>        
                </tr>
           	</thead>           	
            <tbody>
            <?
            $i = 1;
            foreach($this->lUsers as $u)
            {
				$users_types = $this->loadData('users_types');
				$users_types->get($u['id_user_type'],'id_user_type');
            ?>
                <tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$u['name']?></td>
                    <td><?=$u['firstname']?></td>
                    <td><?=$u['email']?></td>
                    <td><?=$users_types->label?></td>
                    <td><?=$this->dates->formatDate($u['added'],'d/m/Y')?></td>
                    <td><?=$this->dates->formatDate($u['updated'],'d/m/Y')?></td>
                    <td><?=$this->dates->formatDate($u['lastlogin'],'d/m/Y')?></td>
                    <td align="center">
						<?
                        if($u['status'] != 2)
                        {
                        ?>
                            <a href="<?=$this->lurl?>/users/status/<?=$u['id_user']?>/<?=$u['status']?>" title="<?=($u['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                            	<img src="<?=$this->surl?>/images/admin/<?=($u['status']==1?'offline':'online')?>.png" alt="<?=($u['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                           	</a> 
                            <a href="<?=$this->lurl?>/users/edit/<?=$u['id_user']?>" class="thickbox">
                            	<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$u['firstname']?> <?=$u['name']?>" />
                           	</a>
                            <a href="<?=$this->lurl?>/users/delete/<?=$u['id_user']?>" title="Supprimer <?=$u['firstname']?> <?=$u['name']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$u['firstname']?> <?=$u['name']?> ?')">
                            	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$u['firstname']?> <?=$u['name']?>" />
                           	</a>
                        <?
                        }
						else
						{
                        ?>
                        	<a href="<?=$this->lurl?>/users/edit/<?=$u['id_user']?>" class="thickbox">
                            	<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$u['firstname']?> <?=$u['name']?>" />
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
    	<p>Il n'y a aucun utilisateur pour le moment.</p>
    <?
	}
	?>
</div>
<?php unset($_SESSION['freeow']); ?>