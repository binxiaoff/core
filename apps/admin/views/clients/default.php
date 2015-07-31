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
        <li><a href="<?=$this->lurl?>/clients" title="Clients">Clients</a> -</li>
        <li>Gestion des clients</li>
    </ul>
    <?
	if(isset($_POST['form_search_client']))
	{
	?>
    	<h1>Résultats de la recherche de clients <?=(count($this->lClients)>0?'('.count($this->lClients).')':'')?></h1>
    <?
	}
	else
	{
	?>
    	<h1>Liste des <?=count($this->lClients)?> derniers clients</h1>
    <?
	}
	?>	
    <div class="btnDroite"><a href="<?=$this->lurl?>/clients/add" class="btn_link thickbox">Ajouter un client</a>&nbsp;&nbsp;&nbsp;<a href="<?=$this->lurl?>/clients/search" class="btn_link thickbox">Rechercher un client</a></div>
    <?
	if(count($this->lClients) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Réf.</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Nb commandes</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lClients as $c)
			{
				// Recuperation du nombre de commandes
				$nbCmds = $this->transactions->counter('id_client = "'.$c['id_client'].'" AND status = 1');
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$c['id_client']?></td>
                    <td><?=$c['nom']?></td>
                    <td><?=$c['prenom']?></td>
                    <td><?=$c['email']?></td>
                    <td><?=$nbCmds?></td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/clients/edit/<?=$c['id_client']?>" class="thickbox">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['nom'].' '.$c['prenom']?>" />
                        </a>
                        <?
						if($nbCmds > 0)
						{
						?>
                        	<a href="<?=$this->lurl?>/clients/detailsClient/<?=$c['id_client']?>" title="Détails du client <?=$c['nom'].' '.$c['prenom']?>">
                            	<img src="<?=$this->surl?>/images/admin/modif.png" alt="Détails du client <?=$c['nom'].' '.$c['prenom']?>" />
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
     	<?
		if(isset($_POST['form_search_client']))
		{
		?>
			<p>Il n'y a aucun client pour cette recherche.</p>
		<?
		}
		else
		{
		?>
			<p>Il n'y a aucun client pour le moment.</p>
		<?
		}
		?>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>