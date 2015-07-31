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
        <li>Logs</li>
    </ul>
	<h1>Historiques des connexions à la partie d'administration du site</h1>
    
    <div class="btnDroite">
        <a href="<?=$this->lurl?>/users/export_logs" class="btn_link">Export</a>
  	</div>
    
    
    <?
	if(count($this->L_Recuperation_logs) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>ID utilisateur</th>
                    <th>Pr&eacute;nom / nom</th>
                    <th>Email</th>
                    <th>Date</th>   
                    <th>IP</th>
                    <th>Pays</th> 
                    <th>Statut</th>     
                </tr>
           	</thead>           	
            <tbody>
            <?
            $i = 1;
            foreach($this->L_Recuperation_logs as $u)
            {
				// si on a un id_user à 0 on essaye de récupérer les infos par rapport à l'adresse mail laissé en post de login
				if($u['id_user'] == 0)
				{
					if($this->users->get($u['email'],'email'))
					{
						$u['id_user'] = "<i>".$this->users->id_user."</i>";
						$u['nom_user'] = "<i>".$this->users->firstname.' '.$this->users->name."</i>";
					}
					
						
				}
            ?>
                <tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td><?=$u['id_user']?></td>
                    <td><?=$u['nom_user']?></td>
                    <td><?=$u['email']?></td>
                    <td><?=$this->dates->formatDate($u['date_connexion'],'d/m/Y H:i:s')?></td>
                    <td><?=$u['ip']?></td>
                    <td><?=$u['pays']?></td>   
                    
                    <?php
					$color= "green";
					switch($u['statut'])
					{
						case 0:
							$color="green";
							$type= "Succ&egrave;s";
						break;
						case 1:
							$type="Echec";
							$color="red";
						break;
						case 2:
							$type = "Mise &agrave; jour du mot de passe";
							$color="orange";
						break;
						
					}
					?>
                    <td style="color:<?=$color?>">
                    	<?=$type?>
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