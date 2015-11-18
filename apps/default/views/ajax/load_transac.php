<tr>
    <th class="narrow-th" width="210">
        <?=$this->lng['profile']['transaction']?>
    </th>
    <th width="240">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-transac-1']?>" class="icon-clock tooltip-anchor"></i></div>
    </th>
    <th width="500">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-transac-2']?>" class="icon-bank tooltip-anchor"></i></div>
    </th>
</tr>
<?

foreach($this->lTrans as $t)
{
    if($t['type_transaction'] == 5)
	{
		$this->echeanciers->get($t['id_echeancier'],'id_echeancier');
		$this->projects->get($this->echeanciers->id_project,'id_project');
		$this->companies->get($this->projects->id_company,'id_company');
	}
	?>
	<tr>
		<td><?=$this->lesStatuts[$t['type_transaction']].($t['type_transaction'] == 5?' - '.$this->companies->name:'')?></td>
		<td><?=$this->dates->formatDate($t['date_transaction'],'d-m-Y')?></td>
		<td><?=$this->ficelle->formatNumber($t['montant']/100)?> €</td>
	</tr>
	<?
}
?>
<script>
$('.tooltip-anchor').tooltip();
</script>