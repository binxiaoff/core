<tr>
    <th width="230">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-1']?>" class="icon-person tooltip-anchor"></i></div>
    </th>
    <th width="82">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-2']?>" class="icon-clock tooltip-anchor"></i></div>
    </th>
    <th width="80">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-3']?>" class="icon-gauge tooltip-anchor"></i></div>
    </th>
    <th width="76">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-4']?>" class="icon-bank tooltip-anchor"></i></div>
    </th>
    <th width="126">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-5']?>" class="icon-calendar tooltip-anchor"></i></div>
    </th>
    <th width="51">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-6']?>" class="icon-graph tooltip-anchor"></i></div>
    </th>
    <th width="124">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-7']?>" class="icon-euro tooltip-anchor"></i></div>
    </th>
    <th width="50">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-8']?>" class="icon-empty-folder tooltip-anchor"></i></div>
    </th>
    <th width="131">
        <div class="th-wrap"><i title="<?=$this->lng['profile']['info-9']?>" class="icon-arrow-next tooltip-anchor"></i></div>
    </th>
</tr>

<?


foreach($this->lLoans as $k => $l)
{
    $this->projects->get($l['id_project'],'id_project');
    $this->companies->get($this->projects->id_company,'id_company');
    //$SumAremb = $this->echeanciers->getSumArembByProject($_POST['id_lender'],$l['id_project']);
    //$SumAremb = $this->echeanciers->select('id_loan = '.$l['id_loan'].' AND ordre = 1');
	$SumAremb = $this->echeanciers->select('id_loan = '.$l['id_loan'].' AND status = 0','ordre ASC',0,1);

	$fiscal = $SumAremb[0]['prelevements_obligatoires']+$SumAremb[0][' 	retenues_source']+$SumAremb[0]['csg']+$SumAremb[0]['prelevements_sociaux']+$SumAremb[0]['contributions_additionnelles']+$SumAremb[0]['prelevements_solidarite']+$SumAremb[0]['crds'];

	$this->projects_status->getLastStatut($l['id_project']);

    ?>
    <tr>
        <td>
            <div class="description">
                <h5><?=$this->companies->name?></h5>
                <h6><?=$this->companies->city?>, <?=$this->companies->zip?></h6>
            </div>
        </td>
        <td><?=$this->dates->formatDate($this->projects->date_fin,'d-m-Y')?></td>
        <td><div class="cadreEtoiles"><div class="etoile <?=$this->lNotes[$this->projects->risk]?>"></div></div></td>
       <td style="white-space: nowrap;"><?=$this->ficelle->formatNumber($l['amount']/100)?> €</td>
        <td><?=$this->dates->formatDate($SumAremb[0]['date_echeance'],'d-m-Y')?></td>
        <td style="white-space: nowrap;"><?=$this->ficelle->formatNumber($l['rate'])?> %</td>
        <td><?=$this->ficelle->formatNumber(($SumAremb[0]['montant']/100)-$fiscal)?> <?=$this->lng['profile']['euros-par-mois']?></td>
        <td>
			<?
			if($this->projects_status->status >= \projects_status::REMBOURSEMENT)
			{
				?><a class="tooltip-anchor icon-pdf" href="<?=$this->lurl.'/pdf/contrat/'.$this->clients->hash.'/'.$l['id_loan']?>"></a><?
			}
			?>
		</td>
        <?
		// smock-it
		if($this->projects->id_project == 1456){
		?>
		<td><a href="<?=$this->lurl.'/pdf/declaration_de_creances/'.$this->clients->hash.'/'.$l['id_loan']?>" class="btn btn-info btn-small multi">Declaration de creances</a></td>
		<?
		}
		else{
			?>
			<td><a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>" class="btn btn-info btn-small"><?=$this->lng['profile']['details']?></a></td>
			<?
		}
		?>

    </tr>
    <?
}
?>
<script>
$('.tooltip-anchor').tooltip();
</script>