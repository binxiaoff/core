<?php
$capital = 100000;
$nbecheances = 36;
$taux = 0.05;
 
 
$txper = $taux / 12;
 
echo 'Taux périodique = '.$txper.'<br />';

 
$echeance = round(( $capital * $txper ) / ( 1 - pow( ( 1 + $txper ), -$nbecheances ) ),2);

echo 'Echéances = '.$echeance.'<br />';
$reste = $echeance*$nbecheances-(round($echeance,2)*($nbecheances-1));
echo 'Dernière échéance = '.($reste).'<br/><br/>';

echo '<strong>Echéancier</strong><br/><br/><table><tr><td>Echeance</td><td>Montant</td><td>Capital</td><td>Interets</td><td></td></tr>';
$cap = $capital;
while($i < $nbecheances)
{
	
	$int = round($txper*$cap,2);
	
	$kech = round((round($echeance,2)-$int),2);
	
	$cap -= $kech;
	
	
	if($i==$nbecheances-1)
	{
		$echeance = $echeance+$cap;
		$cap = 0;
	}
	
	echo '<tr><td>'.($i+1).'</td><td>'.$echeance.'</td><td>'.$kech.'</td><td>'.$int.'</td><td>'.$cap.'</td></tr>';	
	$i++;
}

echo '</table>';

