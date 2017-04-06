<?php

/**
 * Class repayment
 */
class repayment
{
    /**
     * @param $fAmount  100000.00
     * @param $iMonthNb 36
     * @param $fRate    0.08
     *
     * @return array
     */
    public static function getRepaymentSchedule($fAmount, $iMonthNb, $fRate)
    {
        $aRepaymentSchedule = array();
        if ($fAmount * $iMonthNb * $fRate > 0) {
            $fRateMonthly      = $fRate / 12;
            $fRepaymentMonthly = round($fAmount * $fRateMonthly / (1 - pow(1 + $fRateMonthly, -$iMonthNb)), 2);
            $fRestOfRepayment  = $fAmount;

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

                $aRepaymentSchedule[$iMonth] = array(
                    'repayment' => round($fRepaymentMonthly, 2),
                    'capital'   => round($fCapital, 2),
                    'interest'  => round($fInterest, 2)
                );
            }
        }

        return $aRepaymentSchedule;
    }

    /**
     * @param float $fAmount        100000.00
     * @param float $iMonthNb       36
     * @param float $fCommissionRate 0.01
     * @param float $fVAT           0.196
     *
     * @return array
     */
    public static function getRepaymentCommission($fAmount, $iMonthNb, $fCommissionRate, $fVAT)
    {
        $aSchedule   = self::getRepaymentSchedule($fAmount, $iMonthNb, $fCommissionRate);
        $fCommission = 0;
        foreach ($aSchedule as $aOrder) {
            $fCommission += $aOrder['interest'];
        }

        $fMonthlyCommission   = round($fCommission / $iMonthNb, 2);
        $fVATAmount           = round($fVAT * $fMonthlyCommission, 2);
        $fVATAmountTotal      = round($fVATAmount * $iMonthNb, 2);
        $fMonthlyCommissionTI = round($fMonthlyCommission + $fVATAmount, 2); //incl tax

        return array(
            'commission_total'            => $fCommission,
            'commission_monthly'          => $fMonthlyCommission,
            'vat_amount_monthly'          => $fVATAmount,
            'commission_monthly_incl_tax' => $fMonthlyCommissionTI,
            'vat_amount_total'            => $fVATAmountTotal,
        );
    }
}
