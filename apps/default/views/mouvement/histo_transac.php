<h2><?=$this->lng['preteur-mouvement']['titre-2']?></h2>
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

			//echo $this->projects_status->status;
			//$SumAremb = $this->echeanciers->getSumArembByProject($this->lenders_accounts->id_lender_account,$l['id_project']);
			//$SumAremb = $this->echeanciers->select('id_loan = '.$l['id_loan'].' AND ordre = 1');
			$SumAremb = $this->echeanciers->select('id_loan = '.$l['id_loan'].' AND status = 0','ordre ASC',0,1);
			/*echo '<pre>';
			print_r($SumAremb);
			echo '</pre>';*/


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
				<td style="white-space: nowrap;"><?=$this->ficelle->formatNumber($l['amount']/100)?> €</td>
				<td><?=$this->dates->formatDate($SumAremb[0]['date_echeance'],'d-m-Y')?></td>
				<td style="white-space: nowrap;"><?=$this->ficelle->formatNumber($l['rate'])?> %</td>
				<td><?=$this->ficelle->formatNumber(($SumAremb[0]['montant']/100)-$fiscal)?> <?=$this->lng['profile']['euros-par-mois']?></td>
				<td>
					<?
                    if($this->projects_status->status >=80)
                    {
                        ?><a class="tooltip-anchor icon-pdf" href="<?=$this->lurl.'/pdf/contrat/'.$this->clients->hash.'/'.$l['id_loan']?>"></a><?
                    }
                    ?>
                </td>
                <?
				//if($_SERVER['REMOTE_ADDR'] != '93.26.42.99'){

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
				/*}
				else{
					?>
                    <td><a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>" class="btn btn-info btn-small"><?=$this->lng['profile']['details']?></a></td>
					<?
				}*/
				?>

			</tr>
			<?
		}
	}
	?>

</table><!-- /.table -->

<div class="table-filter clearfix">
    <p class="left"><?=$this->lng['profile']['alimentation-du-compte-unilend']?><?=$this->clients->id_client?></p>
    <div class="select-box right">
        <select name="" id="annee" class="custom-select field-mini" onchange="load_transac(this.value,'<?=$this->clients->id_client?>')">
            <option value="<?=date('Y')?>"><?=$this->lng['profile']['annee']?> <?=date('Y')?></option>
			<?
            for($i=date('Y');$i>=2009;$i--){
                ?><option value="<?=$i?>"><?=$this->lng['profile']['annee']?> <?=$i?></option><?
            }
            ?>
        </select>
    </div>
</div>

<table class="table transactions-history transac">
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
	if($this->lTrans != false)
	{
		foreach($this->lTrans as $t)
		{

			if($t['type_transaction'] == 5)
			{
				$this->echeanciers->get($t['id_echeancier'],'id_echeancier');
				$this->projects->get($this->echeanciers->id_project,'id_project');
				$this->companies->get($this->projects->id_company,'id_company');
			}



			// ajout KLE 03/03/15 , pour un client à a du lui faire un retrait positif car :
			/*

			Dans le fichier BNP Paribas, nous constatons en date du 25/02/2015 un rejet de virement de EUR 350,00 avec le libellé Christophe Voliotis au motif suivant « Compte clos ».

			Rep :
			-	La régularisation devra s’effectuer en date du jour (et non pas en corrigeant la ligne correspondant à la date où avait été demandé ce virement).

			*/

			$type = "";
			if($t['type_transaction'] == 8 && $t['montant'] > 0)
			{
				$type = "Annulation retrait des fonds - compte bancaire clos";
			}
			else
			{
				$type = $this->lesStatuts[$t['type_transaction']].($t['type_transaction'] == 5?' - '.$this->companies->name:'');
			}


			?>
			<tr>
				<td><?=$type?></td>
				<td><?=$this->dates->formatDate($t['date_transaction'],'d-m-Y')?></td>
				<td><?=$this->ficelle->formatNumber($t['montant']/100)?> €</td>
			</tr>
			<?
		}
	}
	?>

</table><!-- /.table -->