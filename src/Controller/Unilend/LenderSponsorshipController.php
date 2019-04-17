<?php

namespace Unilend\Controller\Unilend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Service\SponsorshipManager;

class LenderSponsorshipController extends Controller
{
    /**
     * @Route("/parrainage", name="lender_sponsorship")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function sponsorshipAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGreantedLenderSponsorship()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');
        try {
            $currentSponsorshipCampaign = $sponsorshipManager->getCurrentSponsorshipCampaign();
        } catch (\Exception $exception) {
            $currentSponsorshipCampaign = null;
            $this->get('logger')->error(
                'Could not find current sponsorship campaign. Exception: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
        $isBlacklisted = $sponsorshipManager->isClientCurrentlyBlacklisted($client);

        if (empty($currentSponsorshipCampaign) || $isBlacklisted) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $translator  = $this->get('translator');
        $sponsorLink = $this->generateUrl('lender_sponsorship_redirect', ['sponsorCode' => $client->getSponsorCode()], UrlGeneratorInterface::ABSOLUTE_URL);

        if ($request->isMethod(Request::METHOD_POST)) {
            $sponseeEmail = $request->request->filter('sponsee-email', null, FILTER_VALIDATE_EMAIL);
            if (empty($sponseeEmail)) {
                $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsee-email-not-valid'));
            }

            $sponseeNames = $request->request->filter('sponsee-names', null, FILTER_SANITIZE_STRING);
            if (empty($sponseeNames)) {
                $this->addFlash('sponsorshipSendMailErrors', $translator->trans('lender-sponsorship_sponsee-names-not-valid'));
            }

            $message = $request->request->filter('sponsor-message', null, FILTER_SANITIZE_STRING);
            if (false === $message) {
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
     *
     * @param string $sponsorCode
     *
     * @return Response
     */
    public function sponsorshipRedirect(string $sponsorCode): Response
    {
        return $this->redirectToRoute('lender_sponsorship_landing_page', [
            'utm_source'   => SponsorshipManager::UTM_SOURCE,
            'utm_source2'  => $sponsorCode,
            'utm_medium'   => SponsorshipManager::UTM_MEDIUM,
            'utm_campaign' => SponsorshipManager::UTM_CAMPAIGN,
            'sponsor'      => $sponsorCode
        ]);
    }
}
