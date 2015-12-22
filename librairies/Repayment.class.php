<?

class repayment
{
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

        $fMonthlyCommission = round($fCommission / $iMonthNb, 2);
        $fVATAmount         = round($fVAT * $fMonthlyCommission, 2);
        $fVATAmountTotal    = round($fVATAmount * $iMonthNb, 2);
        //incl tax
        $fMonthlyCommissionTI = round($fMonthlyCommission + $fVATAmount, 2);

        return array(
            'commission_total' => $fCommission,
            'commission_monthly' => $fMonthlyCommission,
            'vat_amount_monthly' => $fVATAmount,
            'commission_monthly_incl_tax' => $fMonthlyCommissionTI,
            'vat_amount_total' => $fVATAmountTotal,
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
            $aOrder['vat_amount']                    = $aCommission['vat_amount_monthly'];
        }

        return array(
            'commission' => $aCommission,
            'repayment_schedule' => $aSchedule
        );
    }
}