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
        <li><a href="<?=$this->lurl?>/stats" title="Stats">Stats</a> -</li>
        <li>Requête étude de la base des prêteurs</li>
    </ul>
	<h1>Requête étude de la base des prêteurs</h1>
    
    <div style="margin-bottom:20px; float:right;"><a href="<?=$this->lurl?>/stats/requete_etude_base_preteurs/csv" class="btn_link">Recuperation du CSV</a></div>
    <?
	if(count($this->result) > 0)
	{
	?>

            <?
            $i = 1;
			while($e = $this->bdd->fetch_array($this->result))
            //foreach($this->lEmpr as $e)
            {		if($i == 1){ ?>			
					    	<table class="tablesorter">
        	<thead>
                <tr><? foreach($e as $key=>$val)if(!is_numeric($key)){ ?>
                <th><?=$key?></th><? } ?>
                </tr>
           	</thead>           	
            <tbody>
                    <? } ?>
					<tr<?=($i%2 == 1?'':' class="odd"')?>>
						<?
                        foreach($e as $key=>$field)if(!is_numeric($key))
						echo "<td>".$field."</td>";
						
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
  	<?
	}
	else
	{
	?>
    	<p>Il n'y a aucun dossier pour le moment.</p>
    <?
	}
	?>
</div>
<?php unset($_SESSION['freeow']); ?>