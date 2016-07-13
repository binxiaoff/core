<?php


namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class LenderProfileController extends Controller
{

    /**
     * @Route("/synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showDashboardAction()
    {

        return $this->render('pages/user_preter_dashboard.twig',
            array()
        );
    }

    /**
     * @Route("/profile", name="lender_profile")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showLenderInformationAction()
    {
        $templateVariables = [];

        /** @var UserLender $user */
        $user = $this->getUser();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        /** @var \pays_v2 $countries */
        $countries = $this->get('unilend.service.entity_manager')->getRepository('pays_v2');
        /** @var \nationalites_v2 $nationalities */
        $nationalities = $this->get('unilend.service.entity_manager')->getRepository('nationalites_v2');
        /** @var \attachment $attachment */
        $attachment = $this->get('unilend.service.entity_manager')->getRepository('attachment');
        /** @var \attachment_type $attachmentType */
        $attachmentType = $this->get('unilend.service.entity_manager')->getRepository('attachment_type');
        /** @var \DateTime $birthDate */
        $birthDate = new \DateTime($client->naissance);
        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Liste deroulante origine des fonds', 'type');
        $templateVariables['originOfFunds'] = explode(';', $settings->value);

        $aClient = $client->select('id_client = ' . $user->getClientId());
        $aLenderAccount = $lenderAccount->select('id_client_owner = ' . $user->getClientId());
        $aClientAddress = $clientAddress->select('id_client = ' . $user->getClientId());
        $templateVariables['client']               = array_shift($aClient);
        $templateVariables['client']['birthYear']  = $birthDate->format('Y');
        $templateVariables['client']['birthMonth'] = $birthDate->format('m');
        $templateVariables['client']['birthDay']   = $birthDate->format('d');
        $templateVariables['lenderAccount']        = array_shift($aLenderAccount);
        $templateVariables['clientAddresses']      = array_shift($aClientAddress);
        $templateVariables['countryList']          = $countries->select('', 'ordre ASC');
        $templateVariables['nationalityList']      = $nationalities->select('', 'ordre ASC');
        $templateVariables['monthList']            = $dates->tableauMois['fr'];

var_dump($templateVariables['countryList']);

        //todo reecrire tableau, passer par un filtre twig pour pays, nationiaté, dates


        return $this->render('pages/lender_profile/lender_info.html.twig', $templateVariables);

    }

    /**
     * @Route("/profile/documents", name="lender_completeness")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showLenderCompletenessForm()
    {
        return $this->render('Ici viendra le formulaire d\'upload des fichiers de complétude');
    }

}