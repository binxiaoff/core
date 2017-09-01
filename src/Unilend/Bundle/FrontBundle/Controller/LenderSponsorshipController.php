<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;


class LenderSponsorshipController extends Controller
{
    /**
     * @Route("/parrainage", name="lender_sponsorship")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function sponsorshipAction(Request $request)
    {
        $sponsorshipManager         = $this->get('unilend.service.sponsorship_manager');
        $isValidatedClient          = $this->getUser()->getClientStatus() >= ClientsStatus::VALIDATED;
        $currentSponsorshipCampaign = $sponsorshipManager->getCurrentSponsorshipCampaign();
        $isBlacklisted              = $sponsorshipManager->isClientCurrentlyBlacklisted($this->getClient());

        if (false == $isValidatedClient || empty($currentSponsorshipCampaign) || $isBlacklisted) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $translator         = $this->get('translator');
        $client             = $this->getClient();
        $sponsorLink        = $this->generateUrl('lender_sponsorship_redirect', ['sponsorCode' => $client->getSponsorCode()], UrlGeneratorInterface::ABSOLUTE_URL);

        $sponseeEmail = $request->request->get('sponsee-email');
        if (
            null === $sponseeEmail
            || false === filter_var($sponseeEmail, FILTER_VALIDATE_EMAIL)
        ) {
            $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsee-email-not-valid'));
        }

        $sponseeNames = $request->request->get('sponsee-names');
        if (
            null === $sponseeNames
            || false === filter_var($sponseeNames, FILTER_SANITIZE_STRING)
        ){
            $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsee-names-not-valid'));
        }

        $message = $request->request->get('sponsor-message');
        if (
            null === $message
            || false === filter_var($message, FILTER_SANITIZE_STRING)
        ){
            $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsor-message-not-valid'));
        }

        if (false === $this->get('session')->getFlashBag()->has('sponsorshipSendMailErrors')) {
            $sponsorshipManager->sendSponsorshipInvitation($client, $sponseeEmail, $sponseeNames, $message);
            $this->addFlash('sponsorshipSendMailSuccess', $translator->trans('lender-sponsorship_send-invitation-success-message'));
        }

        return $this->render('/pages/lender_sponsorship.html.twig', [
            'sponsorLink'     => $sponsorLink,
            'currentCampaign' => $currentSponsorshipCampaign,
            'client'          => $client
        ]);
    }

    /**
     * @Route("/p/{sponsorCode}", name="lender_sponsorship_redirect")
     * @param string $sponsorCode
     *
     * @return Response
     */
    public function sponsorshipRedirect($sponsorCode)
    {
        return $this->redirectToRoute('lender_landing_page', [
            'utm_source'   => SponsorshipManager::UTM_SOURCE,
            'utm_source2'  => $sponsorCode,
            'utm_campaign' => SponsorshipManager::UTM_CAMPAIGN,
            'sponsor'      => $sponsorCode
        ]);
    }

    /**
     * @return Clients
     */
    private function getClient()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var Clients $client */
        $client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);

        return $client;
    }
}
