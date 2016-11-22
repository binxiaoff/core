<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

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
            'isAutobidQualified' => $this->get('unilend.service.autobid_settings_manager')->isQualified($this->getLenderAccount())
        ];

        return $this->render('frontbundle/lender_account/partials/lender_account_nav.html.twig', $template);
    }

    /**
     * @return \lenders_accounts
     */
    private function getLenderAccount()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($clientId, 'id_client_owner');

        return $lenderAccount;
    }
}
