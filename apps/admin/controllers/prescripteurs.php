<?php

class prescripteursController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        // Controle d'acces à la rubrique
        $this->users->checkAccess('emprunteurs');

        // Activation du menu
        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        header('Location:' . $this->lurl . '/prescripteur/gestion');
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
        }
    }

    public function _add_client()
    {
        $this->hideDecoration();

        if (false === empty($_POST)) {
            $this->autoFireView = false;

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            /** @var \clients $client */
            $client = $this->loadData('clients');
            $client->civilite  = $_POST['civilite'];
            $client->nom       = $this->ficelle->majNom($_POST['nom']);
            $client->prenom    = $this->ficelle->majNom($_POST['prenom']);
            $client->email     = trim($_POST['email']);
            $client->telephone = str_replace(' ', '', $_POST['telephone']);
            $client->id_langue = 'fr';
            $client->create();

            /** @var \clients_adresses $clientAddress */
            $clientAddress            = $this->loadData('clients_adresses');
            $clientAddress->adresse1  = $_POST['adresse'];
            $clientAddress->ville     = $_POST['ville'];
            $clientAddress->cp        = $_POST['cp'];
            $clientAddress->id_client = $client->id_client;
            $clientAddress->create();

            /** @var \companies $company */
            $company      = $this->loadData('companies');
            if ($_POST['siren']) {
                $sirenCompany = $company->select('siren = ' . $_POST['siren'], 'added ASC', 0, 1);
            }

            if (false === empty($sirenCompany)) {
                $companyId = $sirenCompany[0]['id_company'];
            } else {
                try {
                    if ($_POST['bic'] && $_POST['iban']) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
                        $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                        /** @var \Doctrine\ORM\EntityManager $entityManager */
                        $entityManager = $this->get('doctrine.orm.entity_manager');

                        $clientEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
                        $bankAccount  = $bankAccountManager->saveBankInformation($clientEntity, $_POST['bic'], $_POST['iban']);
                        if ($bankAccount) {
                            $bankAccountManager->validateBankAccount($bankAccount);
                        }
                    }
                } catch (Exception $exception) {
                    echo json_encode([
                        'result' => 'KO'
                    ]);
                    exit;
                }

                $company->siren = $_POST['siren'];
                $company->name  = $_POST['company_name'];

                $companyId = $company->create();
            }

            /** @var \prescripteurs $advisor */
            $advisor            = $this->loadData('prescripteurs');
            $advisor->id_client = $client->id_client;
            $advisor->id_entite = $companyId;
            $advisor->create();

            if (false === empty($_POST['id_project'])) {
                $this->addAdvisorToProject($_POST['id_project'], $advisor->id_prescripteur);
            }

            $this->users_history->histo(5, 'add prescripteur', $_SESSION['user']['id_user'], serialize(['id_prescripteur' => $advisor->id_prescripteur, 'post' => $_POST]));

            echo json_encode([
                'result'          => 'OK',
                'id_prescripteur' => $advisor->id_prescripteur
            ]);
        }
    }

    public function _search_ajax()
    {
        $this->hideDecoration();
        $this->aClients = array();

        if (isset($this->params[1])) {
            $sSearch = $this->params[1];
            $this->clients  = $this->loadData('clients');
            $this->aClients = $this->clients->searchPrescripteur('', $sSearch, $sSearch, $sSearch, $sSearch, $sSearch, 0, 30, 'OR');
        }

        if (isset($_POST['valider_search_prescripteur'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $iProjectId = $_POST['project'];
            $iAdvisorId = $_POST['prescripteur'];

            $this->addAdvisorToProject($iProjectId, $iAdvisorId);

            $this->autoFireView = false;
            echo json_encode(array('result' => 'OK', 'id_prescripteur' => $iAdvisorId));
        }
    }

    private function addAdvisorToProject($iProjectId, $iAdvisorId)
    {
        $oProject = $this->loadData('projects');
        $oProject->get($iProjectId);
        $oProject->id_prescripteur = $iAdvisorId;
        $oProject->update();
    }
}
