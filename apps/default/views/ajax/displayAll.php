<table class="table orders-table">
    <tr>
        <th width="125"><span id="triNum">N°<i class="icon-arrows"></i></span></th>
        <th width="180">
            <span id="triTx"><?=$this->lng['preteur-projets']['taux-dinteret']?> <i class="icon-arrows"></i></span>
            <small><?=$this->lng['preteur-projets']['taux-moyen']?> : <?=$this->ficelle->formatNumber($this->avgRate, 1)?> %</small>
        </th>
        <th width="214">
            <span id="triAmount"><?=$this->lng['preteur-projets']['montant']?> <i class="icon-arrows"></i></span>
            <small><?=$this->lng['preteur-projets']['montant-moyen']?> : <?=$this->ficelle->formatNumber($this->avgAmount/100)?> €</small>
        </th>
        <th width="101"><span id="triStatuts"><?=$this->lng['preteur-projets']['statuts']?> <i class="icon-arrows"></i></span></th>
    </tr>
    <?

    foreach($this->lEnchere as $key => $e)
    {
		if($this->lenders_accounts->id_lender_account == $e['id_lender_account']) $vous = true;
		else $vous = false;

		?><tr <?=($vous==true?' class="enchereVousColor"':'')?>>
			<td><?=($vous==true?'<span class="enchereVous">'.$this->lng['preteur-projets']['vous'].' : &nbsp;&nbsp;&nbsp;'.$e['ordre'].'</span>':$e['ordre'])?></td>
			<td><?=$this->ficelle->formatNumber($e['rate'], 1)?> %</td>
			<td><?=$this->ficelle->formatNumber($e['amount']/100, 0)?> €</td>
			<td class="<?=($e['status']==1?'green-span':($e['status']==2?'red-span':''))?>"><?=$this->status[$e['status']]?></td>
		</tr><?
    }
	?>
</table>
<div id="displayAll"></div>

<script>
$("#direction").html('<?=$this->direction?>');

$("#triNum").click(function() {
	$("#tri").html('ordre');
	$("#displayAll").click();
});

$("#triTx").click(function() {

	$("#tri").html('rate');
	$("#displayAll").click();
});

$("#triAmount").click(function() {

	$("#tri").html('amount');
	$("#displayAll").click();
});

$("#triStatuts").click(function() {
	$("#tri").html('status');
	$("#displayAll").click();
});

$("#displayAll").click(function() {
	var tri = $("#tri").html();
	var direction = $("#direction").html();
	$.post(add_url + '/ajax/displayAll', {id: <?=$this->projects->id_project?>,tri:tri,direction:direction}).done(function(data) {
		$('#bids').html(data)
	});
});

</script>