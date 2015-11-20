<h1>Fonds déposés sur la plateforme (<?=$this->year?>)</h1>
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
<h1><?=$this->dates->tableauMois['fr'][date('n')].' '.$this->year?></h1>
    <table class="recapDashboard">
        <tr>
            <th>Prêteurs connectés :</th>
            <td><?=$this->nbPreteurLogin?></td>
            <th>Fonds déposés :</th>
            <td><?= $this->ficelle->formatNumber($this->nbFondsDeposes) ?> €</td>
            <th>Emprunteurs connectés :</th>
            <td><?=$this->nbEmprunteurLogin?></td>
            <th>Dossiers déposés :</th>
            <td><?=$this->nbDepotDossier?></td>
        </tr>
        <tr>
            <th>Prêteurs inscrits :</th>
            <td><?=$this->nbInscriptionPreteur?></td>
            <th>Fonds prêtés :</th>
            <td><?= $this->ficelle->formatNumber($this->nbFondsPretes) ?> €</td>
            <th>Emprunteurs inscrits :</th>
            <td><?=$this->nbInscriptionEmprunteur?></td>
            <th>Total capital restant dus :</th>
            <td><?= $this->ficelle->formatNumber($this->TotalCapitalRestant) ?> €</td>
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
    <h1>Ratios <?=$this->dates->tableauMois['fr'][date('m')].' '.$this->year?></h1>
    <table class="ratioDashboard">
        <tr>
            <th>% Dossier :</th>
            <td><?= $this->ficelle->formatNumber($this->ratioProjects) ?> %</td>
            <th>Montant déposé moyen :</th>
            <td><?= $this->ficelle->formatNumber($this->moyenneDepotsFonds) ?> €</td>
            <th>part de reprêt sur 1 financement :</th>
            <td><?= $this->ficelle->formatNumber($this->tauxRepret) ?> %</td>
            <th>Taux attrition :</th>
            <td><?= $this->ficelle->formatNumber($this->tauxAttrition) ?> %</td>
        </tr>
    </table>
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
							echo $this->caParmoisPart[$part['id_type']][$i].($i!=12?',':'');
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