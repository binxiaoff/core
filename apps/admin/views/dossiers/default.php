<script type="text/javascript">
	$(document).ready(function(){
		
		
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		$("#datepik_1").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		$("#datepik_2").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		
		
		$("#Reset").click(function() {
			$("#id").val('');
			$("#siren").val('');
			$("#datepik_1").val('');
			$("#datepik_2").val('');
			$('#montant option[value="0"]').attr('selected', true);
			$('#duree option[value=""]').attr('selected', true);
			$('#status option[value=""]').attr('selected', true);
			$('#analyste option[value="0"]').attr('selected', true);
		});
		
		$(".tablesorter").tablesorter({headers:{9:{sorter: false},5: { sorter:'digit' }}});	
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
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li>Gestion des dossiers</li>
    </ul>
    <?
	if(isset($_POST['form_search_client']))
	{
	?>
    	<h1>Résultats de la recherche de dossiers <?=(count($this->lProjects)>0?'('.count($this->lProjects).')':'')?></h1>
    <?
	}
	else
	{
	?>
    	<h1>Liste des <?=count($this->lProjects)?> derniers dossiers</h1>
    <?
	}
	?>	
    <div class="btnDroite"><a href="<?=$this->lurl?>/dossiers/add/create" class="btn_link">Créer un dossier</a></div>
    
    <style>
		table.formColor{width:1115px;}
		.select{width: 100px;}
	</style>
    <div style="width:1115px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
    <form method="post" name="search_dossier" id="search_dossier" enctype="multipart/form-data" action="<?=$this->lurl?>/dossiers" target="_parent">
        <fieldset>
            <table class="formColor">
                <tr>
                	<td><label for="id">ID :</label><br /><input type="text" name="id" id="id" class="input_court" title="id" value="<?=$_POST['id']?>"/></td>
                	<td><label for="siren">Siren :</label><br /><input type="text" name="siren" id="siren" class="input_moy" title="siren" value="<?=$_POST['siren']?>"/></td>
                    <td><label for="siren">Raison sociale :</label><br /><input type="text" name="raison-sociale" id="raison-sociale" class="input_moy" title="Raison sociale" value="<?=$_POST['raison-sociale']?>"/></td>
                    
                    <td style="padding-top:23px;"><input type="text" name="date1" id="datepik_1" class="input_dp" value="<?=$_POST['date1']?>"/></td>
                    <td style="padding-top:23px;"><input type="text" name="date2" id="datepik_2" class="input_dp" value="<?=$_POST['date2']?>"/></td>
                    <td style="padding-top:23px;">
                    	<select name="montant" id="montant" class="select">
                        	<option value="0">Montant</option>
                        	<option <?=(isset($_POST['montant']) && $_POST['montant'] == 50?'selected':'')?> value="50">50</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 100?'selected':'')?> value="100">100</option>
                        	<option <?=(isset($_POST['montant']) && $_POST['montant'] == 500?'selected':'')?> value="500">500</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 1000?'selected':'')?> value="1000">1 000</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 1500?'selected':'')?> value="1500">1 500</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 2000?'selected':'')?> value="2000">2 000</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 5000?'selected':'')?> value="2000">5 000</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 10000?'selected':'')?> value="10000">10 000</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 50000?'selected':'')?> value="50000">50 000</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 100000?'selected':'')?> value="100000">100 000</option>
                            <option <?=(isset($_POST['montant']) && $_POST['montant'] == 250000?'selected':'')?> value="250000">250 000</option>
                        </select>
                    	
                    </td>
                   	<td style="padding-top:23px;">
                    	<select name="duree" id="duree" class="select">
                        	<option value="">Durée</option>
                            
                            <option <?=(isset($_POST['duree']) && $_POST['duree'] == '24'?'selected':'')?> value="24">24 mois</option>
                            <option <?=(isset($_POST['duree']) && $_POST['duree'] == '36'?'selected':'')?> value="36">36 mois</option>
                            <option <?=(isset($_POST['duree']) && $_POST['duree'] == '48'?'selected':'')?> value="48">48 mois</option>
                            <option <?=(isset($_POST['duree']) && $_POST['duree'] == '60'?'selected':'')?> value="60">60 mois</option>
                            <option <?=(isset($_POST['duree']) && $_POST['duree'] == '1000000'?'selected':'')?> value="1000000">je ne sais pas</option>
                        </select>
                    	
                    </td>
                    
                    <td style="padding-top:23px;">
                    	<select name="status" id="status" class="select">
                        	<option value="">Status</option>
                            <?
							foreach($this->lProjects_status as $s)
							{
								?><option <?=(isset($_POST['status']) && $_POST['status'] == $s['status'] || $this->params[0] == $s['status']?'selected':'')?> value="<?=$s['status']?>"><?=$s['label']?></option><?
							}
							?>
                        </select>
                    	
                    </td>
                    <td style="padding-top:23px;">
                    	<select name="analyste" id="analyste" class="select">
                        	<option value="0">Analyste</option>
                            <?
							foreach($this->lUsers as $u)
							{
								?><option <?=(isset($_POST['analyste']) && $_POST['analyste'] == $u['id_user']?'selected':'')?> value="<?=$u['id_user']?>"><?=$u['firstname']?> <?=$u['name']?></option><?
							}
							?>
                        </select>
                    	
                    </td>
                </tr>
                <tr>
                	<th colspan="8" style="text-align:center;">
                        <input type="hidden" name="form_search_dossier" id="form_search_dossier" />
                        <input type="submit" value="Valider" title="Valider" name="send_dossier" id="send_dossier" class="btn" />
                        <input style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;" type="button" value="Reset" title="Reset" name="Reset" id="Reset" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
    </div>
    
    <?
	if(count($this->lProjects) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                	<th>ID</th>
                	<th>Siren</th>
                    <th>Raison sociale</th>
                    <th>Date demande</th>
                    <th>Date modification</th>
                    <th>Montant</th>
                    <th>Durée</th>
                    <th>Statut</th>
                  	<th>Analyste</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lProjects as $p)
			{	
				$this->users->get($p['id_analyste'],'id_user');
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?> id="ledossier<?=$p['id_project']?>">
                	<td><?=$p['id_project']?></td>
                	<td><?=$p['siren']?></td>
                    <td><?=$p['name']?> <?=($p['statusProject']==1?'<i style="color:red">(Hors ligne)</i>':'')?></td>
                    <td><?=$this->dates->formatDate($p['added'],'d/m/Y')?></td>
                    <td><?=$this->dates->formatDate($p['updated'],'d/m/Y')?></td>
                    <td><?=number_format($p['amount'],2,',',' ')?> €</td>
                    <td><?=($p['period'] == 1000000?'Je ne sais pas':$p['period'].' mois')?></td>
                    <td><?=$p['label']?></td>
                    <td><?=$this->users->firstname?> <?=$this->users->name?></td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/dossiers/edit/<?=$p['id_project']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$p['title']?>" />
                        </a>
                        <script>
						$("#ledossier<?=$p['id_project']?>").click(function() {
							$(location).attr('href','<?=$this->lurl?>/dossiers/edit/<?=$p['id_project']?>');
						});
						</script>
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
		if(isset($_POST['form_search_emprunteur']))
		{
		?>
			<p>Il n'y a aucun dossier pour cette recherche.</p>
		<?
		}
		/*else
		{
		?>
			<p>Il n'y a aucun dossier pour le moment.</p>
		<?
		}*/
		?>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>