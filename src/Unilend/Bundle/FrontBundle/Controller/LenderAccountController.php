<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;


class LenderAccountController extends Controller
{
    /**
     * @param string $route
     * @return Response
     */
    public function lenderMenuAction($route)
    {
        $template = [
            'route'              => $route,
            'isAutobidQualified' => $this->get('unilend.service.autobid_settings_manager')->isQualified($this->getClient()),
            'isValidatedClient'  => $this->getUser()->getClientStatus() >= \clients_status::VALIDATED
        ];

        return $this->render('frontbundle/lender_account/partials/lender_account_nav.html.twig', $template);
    }

    private function getClient()
    {
        /** @var ClientsRepository $clientRepository */
        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');

        return $clientRepository->find($this->getUser()->getClientId());
    }
}
