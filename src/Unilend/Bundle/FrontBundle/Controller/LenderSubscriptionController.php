<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Service\SubscriptionManager;

class LenderSubscriptionController extends Controller
{
    /**
     * @Route("inscription-preteur", name="lender_subscription")
     */
    public function lenderSubscriptionShow(Request $request)
    {
        /** @var SubscriptionManager $subscriptionManager */
        $subscriptionManager = $this->get('unilend.frontbundle.service.subscription_manager');

        $aPageData = [
            'countryList'     => $subscriptionManager->getCountryList(),
            'nationalityList' => $subscriptionManager->getNationalityList()
        ];

        if (false === is_null($request->getSession()->get('forms/lender_subscription_step_1'))) {
            $aFormData             = $request->getSession()->get('forms/lender_subscription_step_1');
            $aPageData['formData'] = $this->addStep1FormDataToSession($aFormData);
            $request->getSession()->remove('forms/lender_subscription_step_1');
        }

        return $this->render('pages/lender_subscription.html.twig', $aPageData);

    }

    /**
     * @Route("inscription-preteur-etape-1", name="lender_subscription_step_1")
     * @Method("POST")
     */
    public function lenderSubscriptionStep1Action(Request $request)
    {
        if ($request->isXMLHttpRequest()) {

            var_dump($aFormData = $request->request);
            /** @var SubscriptionManager $subscriptionManager */
            $subscriptionManager = $this->get('unilend.frontbundle.service.subscription_manager');
            $subscriptionManager->handleSubscriptionStepOneData($aFormData);

            $aSessionFormData = $this->addStep1FormDataToSession($aFormData);
            $request->getSession()->set('forms/lender-subscription-step-1', $aSessionFormData);

        }
    }

    private function addStep1FormDataToSession($aFormData)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var BaseUser $user */
            $user = $this->get('security.token_storage')->getToken()->getUser();
            /** @var \clients $client */
            $client = $entityManager->getRepository('clients');
            $client->get($user->getClientId());
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $entityManager->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');
            /** @var \clients_adresses $clientAddress */
            $clientAddress = $entityManager->getRepository('clients_adresses');
            $clientAddress->get($user->getClientId());
        }

        $aSessionFormData[''] = false === empty($aFormData['']) ? $aFormData[''] :  isset($aClientData['']) ? $aClientData[''] : '';
        //TODO implement that for each field of the form

        return $aSessionFormData;
    }

}
