<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Route, Security
};
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class SimulatorController extends Controller
{
    /**
     * @Route("partenaire/simulateur-cout", name="partner_cost_simulator")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function simulatorAction()
    {
        /** @var UserPartner $partnerUser */
        $partnerUser             = $this->getUser();
        $partner                 = $partnerUser->getPartner();
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $projectManager          = $this->get('unilend.service.project_manager');
        $ratesByPeriod           = [];
        $partnerProduct          = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')->findOneBy(['idPartner' => $partner], ['commissionRateFunds' => 'ASC']); // For the moment, all partner products have the same rates
        $vatRate                 = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT)->getRate();
        $fundingDurationsSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Durée des prêts autorisées'])->getValue();
        $fundingDurations        = explode(',', $fundingDurationsSetting);
        $minDuration             = min($fundingDurations);
        $maxDuration             = max($fundingDurations);
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->get('unilend.service.entity_manager')->getRepository('project_rate_settings');
        $rateSettings        = $projectRateSettings->getSettings();
        $lastUpdate          = \DateTime::createFromFormat('Y-m-d H:i:s', max(array_column($rateSettings, 'updated')));

        foreach ($rateSettings as $rateSetting) {
            if ($rateSetting['min'] >= $minDuration && $rateSetting['max'] <= $maxDuration) {
                $ratesByPeriod[$rateSetting['id_period']][$rateSetting['evaluation']] = $rateSetting;
            }
        }

        return $this->render('/partner_account/simulator.html.twig', [
            'fundingDurations'    => $fundingDurations,
            'minAmount'           => $projectManager->getMinProjectAmount(),
            'maxAmount'           => $projectManager->getMaxProjectAmount(),
            'fundsCommission'     => $partnerProduct->getCommissionRateFunds(),
            'repaymentCommission' => $partnerProduct->getCommissionRateRepayment(),
            'vatRate'             => $vatRate,
            'ratesByPeriod'       => $ratesByPeriod,
            'lastUpdate'          => $lastUpdate
        ]);
    }
}
