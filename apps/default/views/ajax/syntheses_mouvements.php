<div style="display:none;" class="interets_recu"><?=$this->lng['preteur-synthese']['interets-recus-par-mois']?></div>
<div style="display:none;" class="capital_rembourse"><?=$this->lng['preteur-synthese']['capital-rembourse-par-mois']?></div>
<div style="display:none;" class="prelevements_fiscaux"><?=$this->lng['preteur-synthese']['prelevements-fiscaux']?></div>
<?
// mois
if($_POST['duree'] == 'mois'){

}
// trimestres
elseif($_POST['duree'] == 'trimestres'){
	/*for($i=1;$i<=3;$i++){
		?>
		<span id="inte<?=$i?>" style="display:none;"><?=$this->sumIntPartrimestre[$i]?></span>
		<span id="remb<?=$i?>" style="display:none;"><?=$this->sumRembPartrimestre[$i]?></span>
		<span id="fiscal<?=$i?>" style="display:none;"><?=$this->sumRevenuesfiscalesPartrimestre[$i]?></span>
		<?
	}*/
}
// annees
else{
	/*$a = 1;
	for($i=$this->debut;$i<=$this->fin;$i++){
		?>
		<span id="inte<?=$a?>" style="display:none;"><?=$this->sumIntParAn[$i]-$this->sumFiscalParAn[$i]?></span>
		<span id="remb<?=$a?>" style="display:none;"><?=$this->sumRembParAn[$i]?></span>
		<span id="fiscal<?=$a?>" style="display:none;"><?=$this->sumFiscalParAn[$i]?></span>
		<?
		$a++;
	}*/
}
?>

<div class="slider-c">
    <div class="arrow prev notext">arrow</div>
    <div class="arrow next notext">arrow</div>
    <div class="chart-slider">
        <?
		if($_POST['duree'] == 'mois'){
			foreach($this->ordre as $key => $o){
				?><div id="bar-mensuels-<?=$o?>" class="chart-item"></div><?
			}
		}
		elseif($_POST['duree'] == 'trimestres'){

			foreach($this->ordre as $key => $o){
				?><div id="bar-mensuels-<?=$o?>" class="chart-item"></div><?
			}
		}
		// Annee
		else{
			$old = 0;
			foreach($this->tab as $key => $t){

				// Si diff on crée le script
				if($old != $t){
					?><div id="bar-mensuels-<?=$t?>" class="chart-item"></div><?
				}
				$old = $t;
			}

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

		$old = 0;
        foreach($this->lesmois as $key => $o){
			$tab = explode('_',$key);
			$annee = $tab[0];
			$mois = $tab[1];

			$intParMois 			= $this->sumIntbParMois[$annee][$mois];
			$rembParMois			= $this->sumRembParMois[$annee][$mois];
			$revenueFiscalsParMois 	= $this->sumRevenuesfiscalesParMois[$annee][$mois];
			?>

				var remb_<?=$key?> = parseFloat('<?=$rembParMois?>');
				var inte_<?=$key?> = parseFloat('<?=$intParMois?>');
				var fiscal_<?=$key?> = parseFloat('<?=$revenueFiscalsParMois?>');

            <?
		}
		foreach($this->lesmois as $key => $o){


			// Si diff on créer le script
			if($old != $o){
				if($old == 0){
					prev($this->lesmois);
				}
				$a = key($this->lesmois);
				$tab = explode('_',$a);
				$a_annee = $tab[0];
				$a_mois = $tab[1];

				next($this->lesmois);
				$b = key($this->lesmois);
				$tab = explode('_',$b);
				$b_annee = $tab[0];
				$b_mois = $tab[1];

				next($this->lesmois);
				$c = key($this->lesmois);
				$tab = explode('_',$c);
				$c_annee = $tab[0];
				$c_mois = $tab[1];

				next($this->lesmois);

				?>

				$('#bar-mensuels-<?=$o?>').highcharts({
					chart: {
						type: 'column',
						backgroundColor:'#fafafa',
						plotBackgroundColor: null,
						plotBorderWidth: null,
						plotShadow: false
					},
					colors: ['#8462a7','#ee5396','#b10366'],
					title: {
						text: ''
					},
					xAxis: {
						color: '#a1a5a7',
						title: {
							enabled: null,
							text : null
						},
						categories: [' <b><?=$this->arrayMois[$a_mois].' - '.$a_annee?></>', ' <b><?=$this->arrayMois[$b_mois].' - '.$b_annee?></b>', ' <b><?=$this->arrayMois[$c_mois].' - '.$c_annee?></b>']
					},
					yAxis: {
						reversedStacks: false,
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
						name: ' <b>'+$('.capital_rembourse').html()+'</b>',
						data:  [
							[' <b>'+remb_<?=$a?>.toString().replace('.',',')+'€</b>', remb_<?=$a?>],
							[' <b>'+remb_<?=$b?>.toString().replace('.',',')+'€</b>', remb_<?=$b?>],
							[' <b>'+remb_<?=$c?>.toString().replace('.',',')+'€</b>', remb_<?=$c?>]
						]
					},
					{
						name: ' <b>'+$('.interets_recu').html()+'</b>',
						data: [
							[' <b>'+inte_<?=$a?>.toString().replace('.',',')+' €</b>', inte_<?=$a?>],
							[' <b>'+inte_<?=$b?>.toString().replace('.',',')+' €</b>', inte_<?=$b?>],
							[' <b>'+inte_<?=$c?>.toString().replace('.',',')+' €</b>', inte_<?=$c?>]]
					},
					{
						name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
						data:  [
							[' <b>'+fiscal_<?=$a?>.toString().replace('.',',')+'€</b>', fiscal_<?=$a?>],
							[' <b>'+fiscal_<?=$b?>.toString().replace('.',',')+'€</b>', fiscal_<?=$b?>],
							[' <b>'+fiscal_<?=$c?>.toString().replace('.',',')+'€</b>', fiscal_<?=$c?>]
						]
					}]
				});

            	<?

			}
			$old = $o;

		}

	}
	elseif($_POST['duree'] == 'trimestres'){


		foreach($this->ordre as $key => $o){

			// inte
			$intParTri1 			= number_format($this->sumIntPartrimestre[$o][1],2,'.','');
			$intParTri2 			= number_format($this->sumIntPartrimestre[$o][2],2,'.','');
			$intParTri3 			= number_format($this->sumIntPartrimestre[$o][3],2,'.','');
			$intParTri4 			= number_format($this->sumIntPartrimestre[$o][4],2,'.','');
			// remb
			$rembParTri1			= number_format($this->sumRembPartrimestre[$o][1],2,'.','');
			$rembParTri2			= number_format($this->sumRembPartrimestre[$o][2],2,'.','');
			$rembParTri3			= number_format($this->sumRembPartrimestre[$o][3],2,'.','');
			$rembParTri4			= number_format($this->sumRembPartrimestre[$o][4],2,'.','');
			// fiscal
			$revenueFiscalsParTri1 	= number_format($this->sumFiscalesPartrimestre[$o][1],2,'.','');
			$revenueFiscalsParTri2 	= number_format($this->sumFiscalesPartrimestre[$o][2],2,'.','');
			$revenueFiscalsParTri3 	= number_format($this->sumFiscalesPartrimestre[$o][3],2,'.','');
			$revenueFiscalsParTri4 	= number_format($this->sumFiscalesPartrimestre[$o][4],2,'.','');
			?>

				var inte1_<?=$o?> = parseFloat('<?=$intParTri1?>');
				var inte2_<?=$o?> = parseFloat('<?=$intParTri2?>');
				var inte3_<?=$o?> = parseFloat('<?=$intParTri3?>');
				var inte4_<?=$o?> = parseFloat('<?=$intParTri4?>');

				var remb1_<?=$o?> = parseFloat('<?=$rembParTri1?>');
				var remb2_<?=$o?> = parseFloat('<?=$rembParTri2?>');
				var remb3_<?=$o?> = parseFloat('<?=$rembParTri3?>');
				var remb4_<?=$o?> = parseFloat('<?=$rembParTri4?>');

				var fiscal1_<?=$o?> = parseFloat('<?=$revenueFiscalsParTri1?>');
				var fiscal2_<?=$o?> = parseFloat('<?=$revenueFiscalsParTri2?>');
				var fiscal3_<?=$o?> = parseFloat('<?=$revenueFiscalsParTri3?>');
				var fiscal4_<?=$o?> = parseFloat('<?=$revenueFiscalsParTri4?>');
		<?
		//}
		//foreach($this->ordre as $key => $o){
			?>
				$('#bar-mensuels-<?=$o?>').highcharts({
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
					categories: [' <b>1<sup>er</sup> trimestre <?=$o?></b>', ' <b>2<sup>eme</sup> trimestre <?=$o?></b>', ' <b>3<sup>eme</sup> trimestre <?=$o?></b>', ' <b>4<sup>eme</sup> trimestre <?=$o?></b>']
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
						[' <b>'+inte1_<?=$o?>.toString().replace('.',',')+' €</b>', inte1_<?=$o?>],
						[' <b>'+inte2_<?=$o?>.toString().replace('.',',')+' €</b>', inte2_<?=$o?>],
						[' <b>'+inte3_<?=$o?>.toString().replace('.',',')+' €</b>', inte3_<?=$o?>],
						[' <b>'+inte4_<?=$o?>.toString().replace('.',',')+' €</b>', inte4_<?=$o?>]
					]
				},
				{
					name: ' <b>'+$('.capital_rembourse').html()+'</b>',
					data:  [
						[' <b>'+remb1_<?=$o?>.toString().replace('.',',')+'€</b>', remb1_<?=$o?>],
						[' <b>'+remb2_<?=$o?>.toString().replace('.',',')+'€</b>', remb2_<?=$o?>],
						[' <b>'+remb3_<?=$o?>.toString().replace('.',',')+'€</b>', remb3_<?=$o?>],
						[' <b>'+remb4_<?=$o?>.toString().replace('.',',')+'€</b>', remb4_<?=$o?>]
					]
				},
				{
					name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
					data:  [
						[' <b>'+fiscal1_<?=$o?>.toString().replace('.',',')+'€</b>', fiscal1_<?=$o?>],
						[' <b>'+fiscal2_<?=$o?>.toString().replace('.',',')+'€</b>', fiscal2_<?=$o?>],
						[' <b>'+fiscal3_<?=$o?>.toString().replace('.',',')+'€</b>', fiscal3_<?=$o?>],
						[' <b>'+fiscal4_<?=$o?>.toString().replace('.',',')+'€</b>', fiscal4_<?=$o?>]
					]
				}]
			});

            <?
		}


	}
	// annee
	else{
		$a = 1;
		foreach($this->tab as $key => $t){

			$rembParMois			= number_format((float)$this->sumRembParAn[$key],2,'.','');
			$intParMois 			= number_format((float)$this->sumIntParAn[$key]-$this->sumFiscalParAn[$key],2,'.','');
			$revenueFiscalsParMois 	= number_format((float)$this->sumFiscalParAn[$key],2,'.','');
			?>
			var remb_<?=$key?> = parseFloat('<?=$rembParMois?>');
			var inte_<?=$key?> = parseFloat('<?=$intParMois?>');
			var fiscal_<?=$key?> = parseFloat('<?=$revenueFiscalsParMois?>');
            <?

		}
		$old = 0;
		foreach($this->tab as $key => $t){

			// Si diff on crée le script
			if($old != $t){


				if($old == 0){
					prev($this->tab);
				}

				$a = key($this->tab);


				next($this->tab);
				$b = key($this->tab);



				next($this->tab);
				$c = key($this->tab);


				next($this->tab);

				?>
				$('#bar-mensuels-<?=$t?>').highcharts({
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
						categories: [' <b><?=$a?></b>', ' <b><?=$b?></b>', ' <b><?=$c?></b>']
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
							['<b>'+inte_<?=$a?>.toString().replace('.',',')+' €</b>', inte_<?=$a?>],
							['<b>'+inte_<?=$b?>.toString().replace('.',',')+' €</b>', inte_<?=$b?>],
							['<b>'+inte_<?=$c?>.toString().replace('.',',')+' €</b>', inte_<?=$c?>]
						]
					},
					{
						name: ' <b>'+$('.capital_rembourse').html()+'</b>',
						data:  [
							[' <b>'+remb_<?=$a?>.toString().replace('.',',')+'€</b>', remb_<?=$a?>],
							[' <b>'+remb_<?=$b?>.toString().replace('.',',')+'€</b>', remb_<?=$b?>],
							[' <b>'+remb_<?=$c?>.toString().replace('.',',')+'€</b>', remb_<?=$c?>]
						]
					},
					{
						name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
						data:  [
							[' <b>'+fiscal_<?=$a?>.toString().replace('.',',')+'€</b>', fiscal_<?=$a?>],
							[' <b>'+fiscal_<?=$b?>.toString().replace('.',',')+'€</b>', fiscal_<?=$b?>],
							[' <b>'+fiscal_<?=$c?>.toString().replace('.',',')+'€</b>', fiscal_<?=$c?>]
						]
					}]
				});
				<?
			}
			$old = $t;
		}



	}
	?>
</script>