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
	<div class="btnDroite" style="margin:0px;">
    	<select id="loadYear" class="select" onchange="loadDashYear(this.value)" style="width:95px;" name="annee">
        <?
		for($i=date('Y');$i>2008;$i--)
		{
			?><option value="<?=$i?>"><?=$i?></option><?
		}
		?>
        </select>
    </div>

    <div class="contentLoadYear">

        <h1>Fonds déposés sur la plateforme (<?=date('Y')?>)</h1>
        <div id="caannuel" style="width: 100%; height: 300px;"></div>
        <br /><br />
        <style>
            table.recapDashboard,table.ratioDashboard{border :2px solid #B10366;width: 100%;}
            table.recapDashboard td,table.recapDashboard th,table.ratioDashboard td, table.ratioDashboard th{padding:10px;}
            table.recapDashboard th{width: 155px;}
            table.ratioDashboard th{width: 195px;}

        </style>
        <div class="btnDroite" style="margin:0px;">
            <select name="annee" id="annee" class="select" style="width:95px;" onchange="recapdashboard(this.value,<?=$this->year?>)">
            <?
            for($i=1;$i<=12;$i++)
            {
                if(strlen($i) < 2) $month = '0'.$i;
                else $month = $i;

                ?><option <?=(date('m') == $month?'selected':'')?> value="<?=$i?>"><?=$this->dates->tableauMois['fr'][$i]?></option><?
            }




            ?>
            </select>
        </div>
        <div id="recapDashboard">
        <h1><?=$this->dates->tableauMois['fr'][date('n')].' '.date('Y')?></h1>
            <table class="recapDashboard">
                <tr>
                    <th>Prêteurs connectés :</th>
                    <td><?=$this->nbPreteurLogin?></td>

                    <th>Fonds déposés :</th>
                    <td><?=number_format($this->nbFondsDeposes,2,',',' ')?> €</td>

                    <th>Emprunteurs connectés :</th>
                    <td><?=(isset($this->nbEmprunteurLogin)) ? $this->nbEmprunteurLogin : 0 ?></td>

                    <th>Dossiers déposés :</th>
                    <td><?=(isset($this->nbDepotDossier)) ? $this->nbDepotDossier : 0 ?></td>
                </tr>
                <tr>
                    <th>Prêteurs inscrits :</th>
                    <td><?=(isset($this->nbInscriptionPreteur)) ? $this->nbInscriptionPreteur : 0 ?></td>

                    <th>Fonds prêtés :</th>
                    <td><?=number_format($this->nbFondsPretes,2,',',' ')?> €</td>

                    <th>Emprunteurs inscrits :</th>
                    <td><?=(isset($this->nbInscriptionEmprunteur)) ? $this->nbInscriptionEmprunteur : 0 ?></td>

                    <th>Total capital restant dus :</th>
                    <td><?=number_format($this->TotalCapitalRestant,2,',',' ')?> €</td>
                </tr>
            </table>
        </div>

        <br /><br /><br />
        <div class="btnDroite" style="margin:0px;">
            <select name="annee" id="annee" class="select" style="width:95px;" onchange="ratioDashboard(this.value,<?=$this->year?>)">
            <?
            for($i=1;$i<=12;$i++)
            {
                if(strlen($i) < 2) $month = '0'.$i;
                else $month = $i;

                ?><option <?=(date('m') == $month?'selected':'')?> value="<?=$i?>"><?=$this->dates->tableauMois['fr'][$i]?></option><?
            }
            ?>
            </select>
        </div>
        <div id="ratioDashboard">
            <h1>Ratios <?=$this->dates->tableauMois['fr'][date('m')].' '.date('Y')?></h1>
            <table class="ratioDashboard">
                <tr>
                    <th>% Dossier :</th>
                    <td><?=number_format($this->ratioProjects,2,',',' ')?> %</td>

                    <th>Montant déposé moyen :</th>
                    <td><?=number_format($this->moyenneDepotsFonds,2,',',' ')?> €</td>

                    <th>part de reprêt sur 1 financement :</th>
                    <td><?=number_format($this->tauxRepret,2,',',' ')?> %</td>

                    <th>Taux attrition :</th>
                    <td><?=number_format($this->tauxAttrition,2,',',' ')?> %</td>
                </tr>
            </table>
        </div>
    </div>

    <br /><br />
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
<script type="text/javascript">
	var chart;
	$(document).ready(function()
	{
		chart = new Highcharts.Chart(
		{
			chart:
			{
				renderTo: 'caannuel',
				defaultSeriesType: 'spline',
				marginRight: 0,
				marginBottom: 35
			},
			colors: ['#B10366','#59AC26','#89A54E','#80699B','#3D96AE','#DB843D','#92A8CD','#A47D7C','#B5CA92'],
			title:
			{
				text: '',
				x: -20 // center
			},
			xAxis:
			{
				categories: ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aout','Sept','Oct','Nov','Déc'],
				title:
				{
					text: 'Mois',
					style:
					{
						color: '#B10366'
					}
				}
			},
			yAxis:
			{
				title:
				{
					text: 'CA (en €)',
					style:
					{
						color: '#B103662'
					}
				},
				min: 0,
				plotLines: [
				{
					value: 0,
					width: 1,
					color: '#B10366'
				}]
			},

			plotOptions:
			{
				column:
				{
					//pointStart: 50
				},
				series:
				{
					cursor: 'pointer',
					marker:
					{
						lineWidth: 1
					}
				}
			},
			legend: {
				 layout: 'vertical',
				 align: 'left',
				 verticalAlign: 'top',
				 x: 0,
				 y: 0,
				 borderWidth: 0
			  },
			series: [
			{
				type: 'spline',
				name: 'Fonds déposés',
				data: [
				<?php
				for($i=1;$i<=12;$i++)
				{
					$i = ($i<10?'0'.$i:$i);
					echo $this->caParmois[$i].($i!=12?',':'');
				}
				?>]
			},
			{
				name: 'Fonds virés',
				data: [
				<?php
				for($i=1;$i<=12;$i++)
				{
					$i = ($i<10?'0'.$i:$i);
					echo $this->VirementsParmois[$i].($i!=12?',':'');
				}
				?>]
			},
			{
				name: 'Fonds remboursés',
				data: [
				<?php
				for($i=1;$i<=12;$i++)
				{
					$i = ($i<10?'0'.$i:$i);
					echo $this->RembEmprParMois[$i].($i!=12?',':'');
				}
				?>]
			}

			<?php
			if(count($this->lTypes) > 0)
			{
				foreach($this->lTypes as $part)
				{
					$this->partenaires_types->get($part['id_type']);
				?>
					,
					{
						name: '<?=$this->partenaires_types->nom?>',
						data: [
						<?php
						for($i=1;$i<=12;$i++)
						{
							$i = ($i<10?'0'.$i:$i);
							if(isset($this->caParmoisPart[$part['id_type']][$i]))
								echo $this->caParmoisPart[$part['id_type']][$i];
							   else
							    echo "0.00";
							if ($i!=12) echo ',';
						}
						?>]
					}
				<?php
				}
			}
			?>
			  ]
		});
	});
</script>