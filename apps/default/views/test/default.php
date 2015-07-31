<br/><strong>Echéancier</strong><br/>

<form action="" method="post">
    <table>
        <tr>
            <td><label>capital : </label></td>
            <td><input type="text" name="capital" value="<?=(isset($_POST['capital'])?$_POST['capital']:'100000')?>"/></td>
        </tr>
        <tr>
            <td><label>nbecheances : </label></td>
            <td><input type="text" name="nbecheances" value="<?=(isset($_POST['nbecheances'])?$_POST['nbecheances']:'36')?>"/></td>
        </tr>
        <tr>
            <td><label>taux : </label></td>
            <td><input type="text" name="taux" value="<?=(isset($_POST['taux'])?$_POST['taux']:'0.05')?>"/></td>
        </tr>
        <tr>
            <td><label>commission : </label></td>
            <td><input type="text" name="commission" value="<?=(isset($_POST['commission'])?$_POST['commission']:'0.01')?>"/></td>
        </tr>
        <tr>
            <td><label>tva : </label></td>
            <td><input type="text" name="tva" value="<?=(isset($_POST['tva'])?$_POST['tva']:'0.196')?>"/></td>
        </tr>
        <tr>
            <td><label>valider : </label></td>
            <td><input type="submit" name="send" value="Valider"/></td>
        </tr>
    </table>
</form>

<table border="1">
    <tr>
        <th>Total Com : </th>
        <td><?=$this->donneesEcheances['totalCom']?> €</td>
    </tr>
    <tr>
        <th>Com / mois : </th>
        <td><?=$this->donneesEcheances['comParMois']?> €</td>
    </tr>
    <tr>
        <th>Com / mois TTC : </th>
        <td><?=$this->donneesEcheances['comParMoisTTC']?> €</td>
    </tr>
    <tr>
        <th>TVA : </th>
        <td><?=$this->donneesEcheances['tvaCom']?> €</td>
    </tr>
    <tr>
        <th>total TVA : </th>
        <td><?=$this->donneesEcheances['totalTvaCom']?> €</td>
    </tr>
</table>

<br/><table border="1"><tr><th>Echeance</td><td>Intérêt</th><th>Capital</th><th>Montant preteur(I+C)</th><th>Com</th><th>TVA</th><th>Montant emprunteur<br />(I+C+ComTTC)</th><th>Capital restant</th></tr>
		<?
		
		$catiptal = 0;
		$interets = 0;
		$commission = 0;
		$montantRembTotal = 0;
		$montant = 0;
		$tvaTotal = 0;
		$cap = 0;
		
		foreach($this->echeancier as $k => $e)
		{
			
			echo '<tr><td>'.$k.'</td><td>'.$e['interets'].'</td><td>'.$e['capital'].'</td><td>'.$e['echeance'].'</td><td>'.$e['commission'].'</td><td>'.$e['tva'].'</td><td>'.$e['echeancePlusComTTC'].'</td><td>'.$e['cap'].'</td></tr>';
			
			$catiptal += $e['capital'];
			$interets += $e['interets'];
			$echeance += $e['echeance'];
			$commission += $e['commission'];
			$tvaTotal += $e['tva'];
			$echeancePlusComTTC += $e['echeancePlusComTTC'];
			
			$cap += $e['cap'];
		}
		echo '<tr><th>Total</th><th>'.$interets.'</th><th>'.$catiptal.'</th><th>'.$echeance.'</th><th>'.$commission.'</th><th>'.$tvaTotal.'</th><th>'.$echeancePlusComTTC.'</th><th>'.$cap.'</th></tr>';
		?>
		</table>

<br /><br />

old 2

<br/><table border="1"><tr><td>Echeance</td><td>Remb Capital</td><td>Remb Intérêt</td><td>Remb Com TTC</td><td>Montant Remb</td><td>Part Prêteur</td><td>TVA</td><td>Capital restant</td></tr>
		<?
		
		$catiptal = 0;
		$interets = 0;
		$commisionTTC = 0;
		$montantRembTotal = 0;
		$montant = 0;
		$tvaTotal = 0;
		$cap = 0;
		
		foreach($this->echeancierold2 as $k => $e)
		{
			
			
			
			
			echo '<tr><td>'.$k.'</td><td>'.$e['capital'].'</td><td>'.$e['interets'].'</td><td>'.($e['commission']).'</td><td>'.$e['MontantRemb'].'</td><td>'.$e['montant'].'</td><td>'.$e['tva'].'</td><td>'.$e['cap'].'</td></tr>';
			
			$catiptal += $e['capital'];
			$interets += $e['interets'];
			$commisionTTC += ($e['commission']);
			$montantRembTotal += $e['MontantRemb'];
			$montant += $e['montant'];
			$tvaTotal += $e['tva'];
			$cap += $e['cap'];
		}
		echo '<tr><th>Total</th><th>'.$catiptal.'</th><th>'.$interets.'</th><th>'.$commisionTTC.'</th><th>'.$montantRembTotal.'</th><th>'.$montant.'</th><th>'.$tvaTotal.'</th><th>'.$cap.'</th></tr>';
		?>
		</table>

<br /><br />

Old

<br />
<br/><table border="1"><tr><td>Echeance</td><td>Remb Capital</td><td>Remb Intérêt</td><td>Remb Com TTC</td><td>Montant Remb</td><td>Part Prêteur</td><td>TVA</td><td>Capital restant</td></tr>
		<?
		
		$catiptal = 0;
		$interets = 0;
		$commisionTTC = 0;
		$montantRembTotal = 0;
		$montant = 0;
		$tvaTotal = 0;
		$cap = 0;
		
		foreach($this->echeancierold as $k => $e)
		{
			
			
			
			
			echo '<tr><td>'.$k.'</td><td>'.$e['capital'].'</td><td>'.$e['interets'].'</td><td>'.($e['commission']+$e['tva']).'</td><td>'.$e['MontantRemb'].'</td><td>'.$e['montant'].'</td><td>'.$e['tva'].'</td><td>'.$e['cap'].'</td></tr>';
			
			$catiptal += $e['capital'];
			$interets += $e['interets'];
			$commisionTTC += ($e['commission']+$e['tva']);
			$montantRembTotal += $e['MontantRemb'];
			$montant += $e['montant'];
			$tvaTotal += $e['tva'];
			$cap += $e['cap'];
		}
		echo '<tr><th>Total</th><th>'.$catiptal.'</th><th>'.$interets.'</th><th>'.$commisionTTC.'</th><th>'.$montantRembTotal.'</th><th>'.$montant.'</th><th>'.$tvaTotal.'</th><th>'.$cap.'</th></tr>';
		?>
		</table>