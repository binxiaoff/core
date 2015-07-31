<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter({headers:{3:{sorter: false}}});	
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
        <li><a href="<?=$this->lurl?>/clients/groupes" title="Groupes de clients">Groupes de clients</a> > </li>
        <li>Liste des clients</li>
    </ul>
	<h1>Liste des client du groupe : <?=$this->groupes->nom?></h1>
    <?
	if(count($this->lClients) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>&nbsp;</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lClients as $c)
			{
				$this->clients->get($c['id_client'],'id_client');
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$this->clients->nom?></td>
                    <td><?=$this->clients->prenom?></td>
                    <td><?=$this->clients->email?></td>
                    <td align="center">
						<a href="<?=$this->lurl?>/clients/detailsGroupe/<?=$this->groupes->id_groupe?>/delete/<?=$c['id_client']?>" title="Supprimer <?=$this->clients->nom.' '.$this->clients->prenom?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$this->clients->nom.' '.$this->clients->prenom?> ?')">
                            <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$this->clients->nom.' '.$this->clients->prenom?>" />
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
    	<p>Il n'y a aucun client pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>