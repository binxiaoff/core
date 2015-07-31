<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter();	
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
	<h1>Requete Données financières emprunteurs</h1>
    
    <div style="margin-bottom:20px; float:right;"><a href="<?=$this->lurl?>/stats/donnees_financieres_emprumteurs/csv" class="btn_link">Recuperation du CSV</a></div>

    <table class="tablesorter">
        <thead>
            <tr>
                <th>Id project</th>
                <th>Name</th>
                <th>Source</th>
                <th>Title</th>   
                <th>added</th>                     
                <th>Status</th>
                <th>altares_scoreVingt</th>   
                <th>Risk</th>
                <th>Amount</th>
                <th>Period</th>
                
                <th>ca2011</th>
                <th>ca2012</th>
                <th>ca2013</th>
                <th>ca2014</th>
                
                <th>rbe2011</th>
                <th>rbe2012</th>
                <th>rbe2013</th>
                <th>rbe2014</th>
                
                <th>rex2011</th>
                <th>rex2012</th>
                <th>rex2013</th>
                <th>rex2014</th>
                
                <th>invest2011</th>
                <th>invest2012</th>
                <th>invest2013</th>
                <th>invest2014</th>
                
                <th>immocorp2011</th>
                <th>immocorp2012</th>
                <th>immocorp2013</th>
                <th>immocorp2014</th>
                
                <th>immoincorp2011</th>
                <th>immoincorp2012</th>
                <th>immoincorp2013</th>
                <th>immoincorp2014</th>
                
                <th>immofin2011</th>
                <th>immofin2012</th>
                <th>immofin2013</th>
                <th>immofin2014</th>
                
                <th>stock2011</th>
                <th>stock2012</th>
                <th>stock2013</th>
                <th>stock2014</th>
                
                <th>creances2011</th>
                <th>creances2012</th>
                <th>creances2013</th>
                <th>creances2014</th>
                
                <th>dispo2011</th>
                <th>dispo2012</th>
                <th>dispo2013</th>
                <th>dispo2014</th>
                
                <th>valeursmob2011</th>
                <th>valeursmob2012</th>
                <th>valeursmob2013</th>
                <th>valeursmob2014</th>
                
                <th>cp2011</th>
                <th>cp2012</th>
                <th>cp2013</th>
                <th>cp2014</th>
                
                <th>provisions2011</th>
                <th>provisions2012</th>
                <th>provisions2013</th>
                <th>provisions2014</th>
                
                <th>ammort2011</th>
                <th>ammort2012</th>
                <th>ammort2013</th>
                <th>ammort2014</th>
                
                <th>dettesfin2011</th>
                <th>dettesfin2012</th>
                <th>dettesfin2013</th>
                <th>dettesfin2014</th>
                
                <th>dettesfour2011</th>
                <th>dettesfour2012</th>
                <th>dettesfour2013</th>
                <th>dettesfour2014</th>
                
                <th>autresdettes2011</th>
                <th>autresdettes2012</th>
                <th>autresdettes2013</th>
                <th>autresdettes2014</th>
                
                <th>forme</th>
                <th>date_creation</th>
            </tr>
        </thead>           	
        <tbody>
        <?
        $i = 1;
		
		$resultat = $this->bdd->query($this->sql);
        while($record = $this->bdd->fetch_array($resultat))
		{
			?>
			<tr<?=($i%2 == 1?'':' class="odd"')?>>
				<?
				for($a=0;$a <=45;$a++){
					?><td><?=$record[$a]?></td><?
				}
				?>
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

</div>
<?php unset($_SESSION['freeow']); ?>