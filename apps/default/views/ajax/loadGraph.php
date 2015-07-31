<script type="text/javascript">
	$(function () {
		$('#legraph').highcharts({
			title: {
				text: '',
				x: 0 //center
			},
			subtitle: {
				text: '',
				x: 0
			},
			xAxis: {
				categories: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
					'Juil', 'Aout', 'Sept', 'Oct', 'Nov', 'Déc']
			},
			yAxis: {
				title: {
					text: '<?=$this->lng['preteur-mouvement']['montant']?>'
				},
				min: 0,
				plotLines: [{
					value: 0,
					width: 1,
					color: '#808080'
				}]
			},
			tooltip: {
				valueSuffix: ' €'
			},
			legend: {
				layout: 'horizontal',
				align: 'center',
				verticalAlign: 'bottom',
				borderWidth: 0
			},
			chart: { 
				backgroundColor:'transparent',
				defaultSeriesType: 'spline'
			},
			series: [{
				name: '<?=$this->lng['preteur-mouvement']['somme-versees']?>',
				color: '#40B34F',
				data: [<?
						for($i=1;$i<=12;$i++)
						{
							$i = ($i<10?'0'.$i:$i);
							echo $this->sumVersParMois[$i].($i!=12?',':'');
						}
						?>]
			}, {
				name: '<?=$this->lng['preteur-mouvement']['somme-pretees']?>',
				color: '#B10366',
				data: [<?
						for($i=1;$i<=12;$i++)
						{
							$i = ($i<10?'0'.$i:$i);
							echo $this->sumPretsParMois[$i].($i!=12?',':'');
						}
						?>]
			}, {
				name: '<?=$this->lng['preteur-mouvement']['argent-rembourse']?>',
				color: '#8462A7',
				data: [<?
						for($i=1;$i<=12;$i++)
						{
							$i = ($i<10?'0'.$i:$i);
							echo $this->sumRembParMois[$i].($i!=12?',':'');
						}
						?>]
			}, {
				name: '<?=$this->lng['preteur-mouvement']['interets-recus']?>',
				color: '#AE6890',
				data: [<?
						for($i=1;$i<=12;$i++)
						{
							$i = ($i<10?'0'.$i:$i);
							echo $this->sumIntbParMois[$i].($i!=12?',':'');
						}
						?>]
			}]
		});
	});
</script>

<div class="year-place-holder" id="legraph" ></div>