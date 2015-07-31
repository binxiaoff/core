<div style="display:none;" class="interets_recu"><?=$this->lng['preteur-synthese']['interets-recus-par-mois']?></div>
<div style="display:none;" class="capital_rembourse"><?=$this->lng['preteur-synthese']['capital-rembourse-par-mois']?></div>
<div style="display:none;" class="prelevements_fiscaux"><?=$this->lng['preteur-synthese']['prelevements-fiscaux']?></div>
<?
// mois
if($_POST['duree'] == 'mois'){
	for($i=1;$i<=12;$i++){
		?>
		<span id="inte<?=$i?>" style="display:none;"><?=$this->sumIntbParMois[$i]?></span>
		<span id="remb<?=$i?>" style="display:none;"><?=$this->sumRembParMois[$i]?></span>
		<span id="fiscal<?=$i?>" style="display:none;"><?=$this->sumRevenuesfiscalesParMois[$i]?></span>
		<?
	}
}
// trimestres
elseif($_POST['duree'] == 'trimestres'){
	for($i=1;$i<=3;$i++){
		?>
		<span id="inte<?=$i?>" style="display:none;"><?=$this->sumIntPartrimestre[$i]?></span>
		<span id="remb<?=$i?>" style="display:none;"><?=$this->sumRembPartrimestre[$i]?></span>
		<span id="fiscal<?=$i?>" style="display:none;"><?=$this->sumRevenuesfiscalesPartrimestre[$i]?></span>
		<?
	}
}
// annees
else{
	$a = 1;
	for($i=$this->debut;$i<=$this->fin;$i++){
		?>
		<span id="inte<?=$a?>" style="display:none;"><?=$this->sumIntParAn[$i]-$this->sumFiscalParAn[$i]?></span>
		<span id="remb<?=$a?>" style="display:none;"><?=$this->sumRembParAn[$i]?></span>
		<span id="fiscal<?=$a?>" style="display:none;"><?=$this->sumFiscalParAn[$i]?></span>
		<?
		$a++;
	}	
}
?>

<div class="slider-c">
    <div class="arrow prev notext">arrow</div>
    <div class="arrow next notext">arrow</div>
    <div class="chart-slider">
        <?
		if($_POST['duree'] == 'mois'){
			for($i=1;$i<=4;$i++){
				?><div id="bar-mensuels-<?=$this->ordre[$i]?>" class="chart-item"></div><?
			}
		}
		elseif($_POST['duree'] == 'trimestres'){
			
			?><div id="bar-mensuels-1" class="chart-item"></div><?

		}
		else{
			?><div id="bar-mensuels-2" class="chart-item"></div><?
			?><div id="bar-mensuels-1" class="chart-item"></div><?
			
		}
        ?>
    </div>
</div>

<script type="text/javascript">

	$('.chart-slider').carouFredSel({
		width: 420,
		height: 260,
		auto: false,
		prev: '.slider-c .arrow.prev',
		next: '.slider-c .arrow.next',
		items: {
			visible: 1
		}
	});

	<?
	if($_POST['duree'] == 'mois'){
		for($i=1;$i<=12;$i++){
			?>
			if($('#remb<?=$i?>').html() === undefined) var remb<?=$i?> = 0;
			else var remb<?=$i?> = parseFloat($('#remb<?=$i?>').html());
			
			if($('#inte<?=$i?>').html() === undefined) var inte<?=$i?> = 0;
			else var inte<?=$i?> = parseFloat($('#inte<?=$i?>').html());
			
			if($('#fiscal<?=$i?>').html() === undefined) var fiscal<?=$i?> = 0;
			else var fiscal<?=$i?> = parseFloat($('#fiscal<?=$i?>').html());
			<?
		}
		?>

		$('#bar-mensuels-1').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396', '#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b>JAN</b>', ' <b>FEV</b>', ' <b>MAR</b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
				name: ' <b>'+$('.interets_recu').html()+'</b>',
				data: [
					[' <b>'+inte1.toString().replace('.',',')+' €</b>',  inte1],
					[' <b>'+inte2.toString().replace('.',',')+' €</b>',  inte2],
					[' <b>'+inte3.toString().replace('.',',')+' €</b>',inte3]]
			},
			{
				name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb1.toString().replace('.',',')+'€</b>',  remb1],

					[' <b>'+remb2.toString().replace('.',',')+'€</b>',  remb2],
					[' <b>'+remb3.toString().replace('.',',')+'€</b>',   remb3]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal1.toString().replace('.',',')+'€</b>',  fiscal1],
					[' <b>'+fiscal2.toString().replace('.',',')+'€</b>',  fiscal2],
					[' <b>'+fiscal3.toString().replace('.',',')+'€</b>',   fiscal3]
				]
			}]
		});
	
		$('#bar-mensuels-2').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396', '#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b>AVR</b>', ' <b>MAI</b>', ' <b>JUIN</b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
			   name: ' <b>'+$('.interets_recu').html()+'</b>',
			   data: [
					[' <b>'+inte4.toString().replace('.',',')+' €</b>',  inte4],
					[' <b>'+inte5.toString().replace('.',',')+' €</b>',  inte5],
					[' <b>'+inte6.toString().replace('.',',')+' €</b>',   inte6]
				]
			},
			{
				name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb4.toString().replace('.',',')+' €</b>',  remb4],
					[' <b>'+remb5.toString().replace('.',',')+'€</b>',  remb5],
					[' <b>'+remb6.toString().replace('.',',')+'€</b>',   remb6]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal4.toString().replace('.',',')+'€</b>',  fiscal4],
					[' <b>'+fiscal5.toString().replace('.',',')+'€</b>',  fiscal5],
					[' <b>'+fiscal6.toString().replace('.',',')+'€</b>',   fiscal6]
				]
			}]
		});
	
		$('#bar-mensuels-3').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396', '#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b>JUIL</b>', ' <b>AOUT</b>', ' <b>SEPT</b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
				name: ' <b>'+$('.interets_recu').html()+'</b>',
				data: [
					[' <b>'+inte7.toString().replace('.',',')+' €</b>',  inte7],
					[' <b>'+inte8.toString().replace('.',',')+' €</b>',  inte8],
					[' <b>'+inte9.toString().replace('.',',')+' €</b>',   inte9]
				]
			},
			{
				 name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb7.toString().replace('.',',')+'€</b>',  remb7],
					[' <b>'+remb8.toString().replace('.',',')+'€</b>',  remb8],
					[' <b>'+remb9.toString().replace('.',',')+'€</b>',   remb9]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal7.toString().replace('.',',')+'€</b>',  fiscal7],
					[' <b>'+fiscal8.toString().replace('.',',')+'€</b>',  fiscal8],
					[' <b>'+fiscal9.toString().replace('.',',')+'€</b>',   fiscal9]
				]
			}]
		});
	
		$('#bar-mensuels-4').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396','#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b>OCT</b>', ' <b>NOV</b>', ' <b>DEC</b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
				name: ' <b>'+$('.interets_recu').html()+'</b>',
				data: [
					[' <b>'+inte10.toString().replace('.',',')+' €</b>',  inte10],
					[' <b>'+inte11.toString().replace('.',',')+' €</b>',  inte11],
					[' <b>'+inte12.toString().replace('.',',')+' €</b>',  inte12]
				]
			},
			{
				name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb10.toString().replace('.',',')+'€</b>',  remb10],
					[' <b>'+remb11.toString().replace('.',',')+'€</b>',  remb11],
					[' <b>'+remb12.toString().replace('.',',')+'€</b>',   remb12]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal10.toString().replace('.',',')+'€</b>',  fiscal10],
					[' <b>'+fiscal11.toString().replace('.',',')+'€</b>',  fiscal11],
					[' <b>'+fiscal12.toString().replace('.',',')+'€</b>',   fiscal12]
				]
			}]
		});
		<?
	}
	elseif($_POST['duree'] == 'trimestres'){
		for($i=1;$i<=4;$i++){
			?>
			if($('#remb<?=$i?>').html() === undefined) var remb<?=$i?> = 0;
			else var remb<?=$i?> = parseFloat($('#remb<?=$i?>').html());
			
			if($('#inte<?=$i?>').html() === undefined) var inte<?=$i?> = 0;
			else var inte<?=$i?> = parseFloat($('#inte<?=$i?>').html());
			
			if($('#fiscal<?=$i?>').html() === undefined) var fiscal<?=$i?> = 0;
			else var fiscal<?=$i?> = parseFloat($('#fiscal<?=$i?>').html());
			<?
		}
		?>
		
		$('#bar-mensuels-1').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396', '#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b>1<sup>er</sup> trimestre</b>', ' <b>2<sup>eme</sup> trimestre</b>', ' <b>3<sup>eme</sup> trimestre</b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
				name: ' <b>'+$('.interets_recu').html()+'</b>',
				data: [
					[' <b>'+inte1.toString().replace('.',',')+' €</b>',  inte1],
					[' <b>'+inte2.toString().replace('.',',')+' €</b>',  inte2],
					[' <b>'+inte3.toString().replace('.',',')+' €</b>',  inte3]
				]
			},
			{
				name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb1.toString().replace('.',',')+'€</b>',  remb1],
					[' <b>'+remb2.toString().replace('.',',')+'€</b>',  remb2],
					[' <b>'+remb3.toString().replace('.',',')+'€</b>',  remb3]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal1.toString().replace('.',',')+'€</b>',  fiscal1],
					[' <b>'+fiscal2.toString().replace('.',',')+'€</b>',  fiscal2],
					[' <b>'+fiscal3.toString().replace('.',',')+'€</b>',  fiscal3]
				]
			}]
		});
	
		<?	
	}
	else{
		$a = 1;
		for($i=$this->debut;$i<=$this->fin;$i++){
			?>
			if($('#remb<?=$a?>').html() === undefined) var remb<?=$a?> = 0;
			else var remb<?=$a?> = parseFloat($('#remb<?=$a?>').html());
			if($('#inte<?=$a?>').html() === undefined) var inte<?=$a?> = 0;
			else var inte<?=$a?> = parseFloat($('#inte<?=$a?>').html());
			if($('#fiscal<?=$a?>').html() === undefined) var fiscal<?=$a?> = 0;
			else var fiscal<?=$a?> = parseFloat($('#fiscal<?=$a?>').html());
			
			<?
			$a++;
		}
		?>
		
		$('#bar-mensuels-1').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396', '#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b><?=$this->debut?></b>', ' <b><?=$this->debut+1?></b>', ' <b><?=$this->debut+2?></b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
				name: ' <b>'+$('.interets_recu').html()+'</b>',
				data: [
					[' <b>'+inte1.toString().replace('.',',')+' €</b>',  inte1],
					[' <b>'+inte2.toString().replace('.',',')+' €</b>',  inte2],
					[' <b>'+inte3.toString().replace('.',',')+' €</b>',  inte3]
				]
			},
			{
				name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb1.toString().replace('.',',')+'€</b>',  remb1],
					[' <b>'+remb2.toString().replace('.',',')+'€</b>',  remb2],
					[' <b>'+remb3.toString().replace('.',',')+'€</b>',  remb3]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal1.toString().replace('.',',')+'€</b>',  fiscal1],
					[' <b>'+fiscal2.toString().replace('.',',')+'€</b>',  fiscal2],
					[' <b>'+fiscal3.toString().replace('.',',')+'€</b>',  fiscal3]
				]
			}]
		});
		
		$('#bar-mensuels-2').highcharts({
			chart: {
				type: 'column',
				backgroundColor:'#fafafa',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false
			},
			colors: ['#ee5396', '#8462a7','#b10366'],
			title: {
				text: ''
			},
			xAxis: {
				color: '#a1a5a7',
				title: {
					enabled: null,
					text : null
				},
				categories: [' <b><?=($this->debut+3)?></b>', ' <b><?=($this->debut+4)?></b>', ' <b><?=($this->debut+5)?></b>']
			},
			yAxis: {
				title: {
					enabled: null,
					text: null
				},
				min: 0
			},
			legend: {
				borderColor: '#ffffff',
				enabled: true
			},
			plotOptions: {
				column: {
					pointWidth: 80,
					stacking: 'normal',
					dataLabels: {
						color: '#fff',
						enabled: true,
						format: '{point.name}'
					}
				}
			},
			tooltip: {
				valueSuffix: ' €',
			},
			series: [
			
			{
				name: ' <b>'+$('.interets_recu').html()+'</b>',
				data: [
					[' <b>'+inte4.toString().replace('.',',')+' €</b>',  inte4],
					[' <b>'+inte5.toString().replace('.',',')+' €</b>',  inte5],
					[' <b>'+inte6.toString().replace('.',',')+' €</b>',  inte6]
				]
			},
			{
				name: ' <b>'+$('.capital_rembourse').html()+'</b>',
				data:  [
					[' <b>'+remb4.toString().replace('.',',')+'€</b>',  remb4],
					[' <b>'+remb5.toString().replace('.',',')+'€</b>',  remb5],
					[' <b>'+remb6.toString().replace('.',',')+'€</b>',  remb6]
				]
			},
			{
				name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
				data:  [
					[' <b>'+fiscal4.toString().replace('.',',')+'€</b>',  fiscal4],
					[' <b>'+fiscal5.toString().replace('.',',')+'€</b>',  fiscal5],
					[' <b>'+fiscal6.toString().replace('.',',')+'€</b>',  fiscal6]
				]
			}]
		});
		
		
		<?
	}
	?>
</script>