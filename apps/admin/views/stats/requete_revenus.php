<?
header("Content-Type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename=requete_revenus.xls");
?>
<table border="1">
<tr>
	<th>CDos</th>
    <th>Cbéné</th>
    <th>CodeV</th>
    <th>Date</th>
    <th>Montant</th>
    <th>Monnaie</th>
    <th>NbreParts</th>
    <th>VAP</th>
</tr>
<?
foreach($this->lProjects as $p){
	
	$this->companies->get($p['id_company'],'id_company');
	
	// Liste des prêts
	$lPrets = $this->loans->select('id_project = '.$p['id_project'].' AND status = 0');
	
	foreach($lPrets as $pret){
		
		if($pret['id_lender'] == '2476'){
		$this->lenders_accounts->get($pret['id_lender'],'id_lender_account');
		
		?>
        <tr>
            <td><?=$this->companies->id_client_owner?></td>
            <td><?=$this->lenders_accounts->id_client_owner?></td>
            <td>117</td>
            <td><?=date('Y-m-d',strtotime($pret['added']))?></td>
            <td><?=($pret['amount']/100)?></td>
            <td>"EURO"</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
		<?
		}
	}
	
	$lEcheances = $this->echeanciers->select('id_project = '.$p['id_project'].' AND status = 1');
	foreach($lEcheances as $e){
		
		$this->lenders_accounts->get($e['id_lender'],'id_lender_account');
		if($pret['id_lender'] == '2476'){
		?>
        <tr>
            <td><?=$this->companies->id_client_owner?></td>
            <td><?=$this->lenders_accounts->id_client_owner?></td>
            <td>53</td>
            <td><?=date('Y-m-d',strtotime($e['date_echeance_reel']))?></td>
            <td><?=($e['interets']/100)?></td>
            <td>"EURO"</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
		<?
		
		?>
        <tr>
            <td><?=$this->companies->id_client_owner?></td>
            <td><?=$this->lenders_accounts->id_client_owner?></td>
            <td>54</td>
            <td><?=date('Y-m-d',strtotime($e['date_echeance_reel']))?></td>
            <td><?=($e['retenues_source']/100)?></td>
            <td>"EURO"</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
		<?
		
		?>
        <tr>
            <td><?=$this->companies->id_client_owner?></td>
            <td><?=$this->lenders_accounts->id_client_owner?></td>
            <td>66</td>
            <td><?=date('Y-m-d',strtotime($e['date_echeance_reel']))?></td>
            <td><?=($e['interets']/100)?></td>
            <td>"EURO"</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
		<?
		
		?>
        <tr>
            <td><?=$this->companies->id_client_owner?></td>
            <td><?=$this->lenders_accounts->id_client_owner?></td>
            <td>118</td>
            <td><?=date('Y-m-d',strtotime($e['date_echeance_reel']))?></td>
            <td><?=($e['capital']/100)?></td>
            <td>"EURO"</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
		<?
		}
	}
	
}
?>
</table>
<?
echo $total.'<br>';
echo $totalpret;
die;