<?

class remb
{
    function echeancier($montant, $nbMois, $tauxAnnuel, $commissionAnnuelle, $tva = 0.196)
    {
        // On divise le le taux annuel du preteur pour avoir le taux mensuel
        $txMensuel = ($tauxAnnuel / 12);
        // On fait la meme chose avec la commission annuelle
        $txComMensuelle = ($commissionAnnuelle / 12);

        //echo 'Taux périodique = '.$txMensuel.'<br />';


        // Calcule pour avoir le montant a verser chaque mois (capital + interets)
        $echeance = round(($montant * $txMensuel) / (1 - pow((1 + $txMensuel), -$nbMois)), 2);


        // Tableau qu'on va remplir
        $ArrayEcheancier = array();

        // on va initialiser la variable $cap, qui est le montant restant à rembourser à un moment spécifique.
        $cap = $montant;

        // On parcour les mensualités
        $i = 0;
        while ($i < $nbMois) {
            // les interets
            $interets = round($txMensuel * $cap, 2);

            // le capital
            $capital = round(($echeance - $interets), 2);

            // Chaque mois on retire le montant persue
            $cap -= $capital;

            // Pour le dernier mois on regularise
            if ($i == $nbMois - 1) {
                // On regarde ce qui reste comme montant et on l'ajout dans le dernier versement
                $echeance = $echeance + $cap;
                $capital  = $capital + $cap;
                // On vide le montant a remb
                $cap = 0;
            }

            $a = $i + 1;

            $ArrayEcheancier[$a]['echeance'] = $echeance;
            $ArrayEcheancier[$a]['capital']  = $capital;                    // montant sans les interets
            $ArrayEcheancier[$a]['interets'] = $interets;                    // interets
            $ArrayEcheancier[$a]['cap']      = $cap;                            // montant qui reste a remboursser


            $i++;
        }

        // com //

        // echeance com
        $echeanceCom = round(($montant * $txComMensuelle) / (1 - pow((1 + $txComMensuelle), -$nbMois)), 2);

        $capCom   = $montant;
        $i        = 0;
        $totalCom = 0;
        while ($i < $nbMois) {
            $com = round($txComMensuelle * $capCom, 2); //利息

            $capitalCom = round(($echeanceCom - $com), 2); //本金


            $capCom -= $capitalCom;// 剩余

            if ($i == $nbMois - 1) {
                // On regarde ce qui reste comme montant et on l'ajout dans le dernier versement
                $echeanceCom = $echeanceCom + $capCom;

                // On vide le montant a remb
                $capCom = 0;
            }

            // Total des com
            $totalCom += $com;

            $i++;
        }

        // fin com //


        // Commission par mois
        $comParMois = $totalCom / $nbMois;

        // Commission TTC par mois (com par mois + tva)
        $comParMoisTTC = $comParMois * (1 + $tva);

        // tva de la com par mois
        $tvaCom = ($comParMois * $tva);

        // tva total de la commission total
        $totalTvaCom = $tvaCom * $nbMois;


        $array1['totalCom']      = $totalCom;
        $array1['comParMois']    = round($comParMois, 2);
        $array1['tvaCom']        = round($tvaCom, 2);
        $array1['comParMoisTTC'] = round($comParMoisTTC, 2);
        $array1['totalTvaCom']   = round($totalTvaCom, 2);


        foreach ($ArrayEcheancier as $k => $e) {
            $ArrayE[$k]['echeance']           = $e['echeance'];                    // montant a remb
            $ArrayE[$k]['capital']            = $e['capital'];                    // montant sans les interets
            $ArrayE[$k]['interets']           = $e['interets'];                    // interets
            $ArrayE[$k]['commission']         = round($comParMois, 2);                    // com
            $ArrayE[$k]['commissionTTC']      = round($comParMoisTTC, 2);                // com ttc (com +tva)
            $ArrayE[$k]['echeancePlusComTTC'] = round($e['echeance'] + $comParMoisTTC, 2); // montant + com ttc
            $ArrayE[$k]['tva']                = round($tvaCom, 2);    // tva
            $ArrayE[$k]['cap']                = $e['cap'];                            // montant qui reste a remboursser

        }

        return array(1 => $array1, 2 => $ArrayE);

    }

    public static function getRepaymentSchedule($fAmount, $iMonthNb, $fRate)
    {
        $aRepaymentSchedule = array();
        if ($fAmount * $iMonthNb * $fRate > 0) {
            $fRateMonthly = $fRate / 12;

            $fRepaymentMonthly = round($fAmount * $fRateMonthly / (1 - pow(1 + $fRateMonthly, -$iMonthNb)), 2);

            $fRestOfRepayment = $fAmount;
            for ($iMonth = 1; $iMonth <= $iMonthNb; $iMonth++) {
                $fInterest = round($fRateMonthly * $fRestOfRepayment, 2);
                $fCapital  = round(($fRepaymentMonthly - $fInterest), 2);
                $fRestOfRepayment -= $fCapital;

                // Adjustment for the last month
                if ($iMonth == $iMonthNb) {
                    $fRepaymentMonthly += $fRestOfRepayment;
                    $fCapital += $fRestOfRepayment;
                    // On vide le montant a remb
                    $fRestOfRepayment = 0;
                }

                $aRepaymentSchedule[$iMonth]['repayment'] = round($fRepaymentMonthly, 2);
                $aRepaymentSchedule[$iMonth]['capital']   = round($fCapital, 2);
                $aRepaymentSchedule[$iMonth]['interest']  = round($fInterest, 2);
                $aRepaymentSchedule[$iMonth]['rest']      = round($fRestOfRepayment, 2);
            }
        }

        return $aRepaymentSchedule;
    }

    public static function getRepaymentCommission($fAmount, $iMonthNb, $fCommisionRate, $fVAT = 0.196)
    {
        $aSchedule   = self::getRepaymentSchedule($fAmount, $iMonthNb, $fCommisionRate);
        $fCommission = 0;
        foreach ($aSchedule as $aOrder) {
            $fCommission += $aOrder['interest'];
        }

        $fMonthlyCommission = $fCommission / $iMonthNb;
        $fVATAmount         = $fVAT * $fMonthlyCommission;
        $fVATAmountTotal    = $fVATAmount * $iMonthNb;
        //incl tax
        $fMonthlyCommissionTI = $fMonthlyCommission + $fVATAmount;

        return array(
            'commission_total' => round($fCommission, 2),
            'commission_monthly' => round($fMonthlyCommission, 2),
            'tav_amount_monthly' => round($fVATAmount, 2),
            'commission_monthly_incl_tax' => round($fMonthlyCommissionTI, 2),
            'tav_amount_total' => round($fVATAmountTotal, 2),
        );
    }

    public static function getRepaymentScheduleWithCommission($fAmount, $iMonthNb, $fRate, $fCommisionRate, $fVAT = 0.196)
    {
        $aCommission = self::getRepaymentCommission($fAmount, $iMonthNb, $fCommisionRate, $fVAT);
        $aSchedule   = self::getRepaymentSchedule($fAmount, $iMonthNb, $fRate);
        foreach ($aSchedule as &$aOrder) {
            $aOrder['commission']                    = $aCommission['commission_monthly'];
            $aOrder['commission_incl_tax']           = $aCommission['commission_monthly_incl_tax'];
            $aOrder['repayment_commission_incl_tax'] = $aOrder['repayment'] + $aCommission['commission_monthly_incl_tax'];
            $aOrder['tav_amount']                    = $aCommission['tav_amount_monthly'];
        }

        return array(
            'commission' => $aCommission,
            'repayment_schedule' => $aSchedule
        );
    }
}