<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class LenderAccountController extends Controller
{
    /**
     * @param string                     $route
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function lenderMenuAction(string $route, ?UserInterface $client): Response
    {

        $template = [
            'route'              => $route,
            'isAutobidQualified' => $this->get('unilend.service.autobid_settings_manager')->isQualified($client)
        ];

        if ($client->isGreantedLenderSponsorship()) {
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');

            $template['currentSponsorshipCampaign'] = $sponsorshipManager->getCurrentSponsorshipCampaign();
            $template['isBlacklisted']              = $sponsorshipManager->isClientCurrentlyBlacklisted($client);
        }

        return $this->render('lender_account/lender_account_nav.html.twig', $template);
    }
}
