<h2><?=$this->lng['preteur-operations']['titre-3']?></h2>
<p><?=$this->lng['profile']['contenu-partie-4']?></p>
<div class="table-filter clearfix">
    <p class="left"><?=$this->lng['profile']['historique-des-projets']?><?=$this->clients->id_client?></p>
    <div class="select-box right">
        <select name="" id="annee" class="custom-select field-mini" onchange="load_finances(this.value,'<?=$this->lenders_accounts->id_lender_account?>')" >
           	<option value="<?=date('Y')?>"><?=$this->lng['profile']['annee']?> <?=date('Y')?></option>
			<?
			for($i=date('Y');$i>=2009;$i--){
				?><option value="<?=$i?>"><?=$this->lng['profile']['annee']?> <?=$i?></option><?	
			}
			?>
        </select>
    </div>
</div>
<table class="table transactions-history finances">
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
	
	if($this->lLoans != false)
	{
		foreach($this->lLoans as $k => $l)
		{
			$this->projects->get($l['id_project'],'id_project');
			$this->companies->get($this->projects->id_company,'id_company');
			
			$this->projects_status->getLastStatut($l['id_project']);
			

			$SumAremb = $this->echeanciers->select('id_loan = '.$l['id_loan'].' AND status = 0','ordre ASC',0,1);

			$fiscal = $SumAremb[0]['prelevements_obligatoires']+$SumAremb[0]['retenues_source']+$SumAremb[0]['csg']+$SumAremb[0]['prelevements_sociaux']+$SumAremb[0]['contributions_additionnelles']+$SumAremb[0]['prelevements_solidarite']+$SumAremb[0]['crds'];
			
			/*echo 'Montant : '.$SumAremb[0]['montant'].'<br>';
			echo 'fiscale : '.$fiscal.'<br>';
			echo (($SumAremb[0]['montant']/100)-$fiscal).'<br>';*/
			
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
				<td style="white-space: nowrap;"><?=number_format($l['amount']/100, 2, ',', ' ')?> €</td>
				<td><?=$this->dates->formatDate($SumAremb[0]['date_echeance'],'d-m-Y')?></td>
				<td style="white-space: nowrap;"><?=number_format($l['rate'], 2, ',', ' ')?> %</td>
				<td><?=number_format(($SumAremb[0]['montant']/100)-$fiscal, 2, ',', ' ')?> <?=$this->lng['profile']['euros-par-mois']?></td>
				<td>
                	<?
					if($this->projects_status->status >=80)
					{
						?><a class="tooltip-anchor icon-pdf" href="<?=$this->lurl.'/pdf/contrat/'.$this->clients->hash.'/'.$l['id_loan']?>"></a><?
					}
					?>
                </td>
				<td><a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>" class="btn btn-info btn-small"><?=$this->lng['profile']['details']?></a></td>
			</tr>
			<?
		}
	}
	?>

</table><!-- /.table -->
