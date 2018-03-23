<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;

class LenderAccountController extends Controller
{
    /**
     * @param string $route
     *
     * @return Response
     */
    public function lenderMenuAction(string $route): Response
    {
        $client   = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $template = [
            'route'              => $route,
            'isAutobidQualified' => $this->get('unilend.service.autobid_settings_manager')->isQualified($client)
        ];

        if (in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_SPONSORSHIP)) {
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');

            $template['currentSponsorshipCampaign'] = $sponsorshipManager->getCurrentSponsorshipCampaign();
            $template['isBlacklisted']              = $sponsorshipManager->isClientCurrentlyBlacklisted($client);
        }

        return $this->render('lender_account/lender_account_nav.html.twig', $template);
    }
}
