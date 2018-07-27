<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus
};

class LenderAccountController extends Controller
{
    /**
     * @param string                $route
     * @param UserInterface|Clients $client
     *
     * @return Response
     */
    public function lenderMenuAction(string $route, UserInterface $client): Response
    {

        $template = [
            'route'              => $route,
            'isAutobidQualified' => $this->get('unilend.service.autobid_settings_manager')->isQualified($client)
        ];

        if (in_array($client->getIdClientStatusHistory()->getId(), ClientsStatus::GRANTED_LENDER_SPONSORSHIP)) {
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');

            $template['currentSponsorshipCampaign'] = $sponsorshipManager->getCurrentSponsorshipCampaign();
            $template['isBlacklisted']              = $sponsorshipManager->isClientCurrentlyBlacklisted($client);
        }

        return $this->render('lender_account/lender_account_nav.html.twig', $template);
    }
}
