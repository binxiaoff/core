<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

        if ($request->isMethod(Request::METHOD_POST)) {
            $sponseeEmail = $request->request->filter('sponsee-email', FILTER_VALIDATE_EMAIL);
            if (null === $sponseeEmail) {
                $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsee-email-not-valid'));
            }

            $sponseeNames = $request->request->filter('sponsee-names', FILTER_SANITIZE_STRING);
            if (null === $sponseeNames){
                $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsee-names-not-valid'));
            }

            $message = $request->request->filter('sponsor-message', FILTER_SANITIZE_STRING);
            if (null === $message){
                $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsor-message-not-valid'));
            }

            if (false === $this->get('session')->getFlashBag()->has('sponsorshipSendMailErrors')) {
                $sponsorshipManager->sendSponsorshipInvitation($client, $sponseeEmail, $sponseeNames, $message);
                $this->addFlash('sponsorshipSendMailSuccess', $translator->trans('lender-sponsorship_send-invitation-success-message'));
            }
        }

        return $this->render('lender_sponsorship/sponsorship.html.twig', [
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
        return $this->redirectToRoute('lender_sponsorship_landing_page', [
            'utm_source'   => SponsorshipManager::UTM_SOURCE,
            'utm_source2'  => $sponsorCode,
            'utm_medium'   => SponsorshipManager::UTM_MEDIUM,
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
