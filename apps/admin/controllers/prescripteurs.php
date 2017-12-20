<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class prescripteursController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/prescripteurs/gestion');
        exit;
    }

    public function _gestion()
    {
        /** @var clients $oClients */
        $oClients = $this->loadData('clients');

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $this->aPrescripteurs = $oClients->searchPrescripteur($this->params[0]);
        } elseif (isset($_POST['form_search_prescripteur'])) {
            $this->aPrescripteurs = $oClients->searchPrescripteur('', $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['company_name'], $_POST['siren']);

            $_SESSION['freeow']['title']   = 'Recherche d\'un prescripteur';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            $this->aPrescripteurs = $oClients->searchPrescripteur('', '', '', '', '', '', '', 10);
        }
    }

    public function _edit()
    {
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->prescripteurs    = $this->loadData('prescripteurs');
        $this->companies        = $this->loadData('companies');
        $this->projects         = $this->loadData('projects');

        if (
            ! isset($this->params[0])
            || ! $this->prescripteurs->get($this->params[0], 'id_prescripteur')
            || ! $this->clients->get($this->prescripteurs->id_client, 'id_client')
            || ! $this->clients_adresses->get($this->clients->id_client, 'id_client')
            || ! $this->companies->get($this->prescripteurs->id_entite, 'id_company')
        ) {
            header('Location:' . $this->lurl . '/prescripteurs/gestion/');
            return;
        }
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->aProjects      = $this->projects->searchDossiers('', '', '', '', '', '', '', '', '', $this->params[0]);
        $this->iProjectsCount = array_shift($this->aProjects);
        $this->bankAccount    = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->prescripteurs->id_client);

        if (isset($_POST['form_edit_prescripteur'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $this->clients->civilite  = $_POST['civilite'];
            $this->clients->nom       = $this->ficelle->majNom($_POST['nom']);
            $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom']);
            $this->clients->email     = trim($_POST['email']);
            $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);
            $this->clients->id_langue = 'fr';
            $this->clients->update();

            $this->clients_adresses->adresse1 = $_POST['adresse'];
            $this->clients_adresses->ville    = $_POST['ville'];
            $this->clients_adresses->cp       = $_POST['cp'];
            $this->clients_adresses->update();

            $this->companies->siren = $_POST['siren'];
            $this->companies->name  = $_POST['company_name'];
            $this->companies->update();

            try {
                if ($_POST['bic'] && $_POST['iban']) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
                    $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                    $clientEntity       = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->prescripteurs->id_client);
                    $bankAccount        = $bankAccountManager->saveBankInformation($clientEntity, $_POST['bic'], $_POST['iban']);
                    if ($bankAccount) {
                        $bankAccountManager->validateBankAccount($bankAccount);
                    }
                }
            } catch (Exception $exception) {
                $_SESSION['freeow']['title']   = 'Error RIB';
                $_SESSION['freeow']['message'] = $exception->getMessage();

                header('Location: ' . $this->lurl . '/prescripteurs/edit/' . $this->prescripteurs->id_prescripteur);
                exit;
            }

            $serialize = serialize(array('id_prescripteur' => $this->prescripteurs->id_prescripteur, 'post' => $_POST));
            $this->users_history->histo(5, 'edit prescripteur', $_SESSION['user']['id_user'], $serialize);

            $_SESSION['freeow']['title']   = 'prescripteur mis a jour';
            $_SESSION['freeow']['message'] = 'le prescripteur a &eacute;t&eacute; mis a jour !';

            header('Location: ' . $this->lurl . '/prescripteurs/edit/' . $this->prescripteurs->id_prescripteur);
            exit;
        }
    }
}
