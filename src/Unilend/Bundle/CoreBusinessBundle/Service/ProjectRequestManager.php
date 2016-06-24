<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectRequestManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getMonthlyRateEstimate()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');

        return round($projects->getGlobalAverageRateOfFundedProjects(50), 1);
    }

    public function getMonthlyPaymentEstimate($amount, $period, $estimatedRate)
    {
        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
        /** @var \PHPExcel_Calculation_Financial $oFinancial */
        $oFinancial = new \PHPExcel_Calculation_Financial();

        $settings->get('TVA', 'type');
        $fVATRate = (float) $settings->value;
        $settings->get('Commission remboursement', 'type');

        $fCommission    = ($oFinancial->PMT($settings->value / 12, $period, -$amount) - $oFinancial->PMT(0, $period, -$amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, -$amount) + $fCommission);

        return $monthlyPayment;
    }

}