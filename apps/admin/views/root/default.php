<script type="text/javascript">
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
	<br />
    <h1><?=count($this->lProjectsNok)?> incidences de remboursement :</h1>
    <?php
	if(count($this->lProjectsNok) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Référence</th>
                    <th>Titre</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
           	</thead>
            <tbody>
            <?php
			$i = 1;
			foreach($this->lProjectsNok as $p)
			{
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                	<td><?=$p['id_project']?></td>
                    <td><?=$p['title_bo']?></td>
                    <td><?=$p['amount']?></td>
                    <td><?=$this->projects_status->getLabel($p['status'])?></td>
                    <td align="center">
                    	<a href="<?=$this->lurl?>/dossiers/edit/<?=$p['id_project']?>" >
                        	<img src="<?=$this->surl?>/images/admin/modif.png" alt="Voir le dossier" title="Voir le dossier" />
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
    }
    else
    {
    ?>
        <p>Il n'y a aucune incidence de remboursement pour le moment.</p>
    <?
    }
    ?>
    <br /><br />
    <h1>Dossiers</h1>
    <?
	if(count($this->lStatus) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th align="center">Statut</th>
                    <th align="center">Résultats</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lStatus as $s)
			{

				$nbProjects = $this->projects->countSelectProjectsByStatus($s['status']);
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td align="center"><a href="<?=$this->lurl?>/dossiers/<?=$s['status']?>"><?=$s['label']?></a></td>
                    <td align="center"><?=$nbProjects?></td>
                </tr>
            <?
				$i++;
            }
            ?>
            </tbody>
        </table>
	<?
    }
    else
    {
    ?>
        <p>Il n'y a aucun statut pour le moment.</p>
    <?
    }
    ?>
</div>