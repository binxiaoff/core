<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;

class emprunteursController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('emprunteurs');

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/dossiers');
        die;
    }

    public function _gestion()
    {
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies        = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');

        if ($this->clients->telephone != '') {
            $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
        }

        if (isset($_POST['form_search_emprunteur'])) {
            $this->lClients = $this->clients->searchEmprunteurs('AND', $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['societe'], $_POST['siren']);

            $_SESSION['freeow']['title']   = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }
    }

    public function _edit()
    {
        $this->clients           = $this->loadData('clients');
        $this->clients_adresses  = $this->loadData('clients_adresses');
        $this->companies         = $this->loadData('companies');
        $this->companies_bilans  = $this->loadData('companies_bilans');
        $this->projects          = $this->loadData('projects');
        $this->projects_status   = $this->loadData('projects_status');
        $this->clients_mandats   = $this->loadData('clients_mandats');
        $this->projects_pouvoir  = $this->loadData('projects_pouvoir');
        $this->settings          = $this->loadData('settings');
        /** @var \company_sector $companySector */
        $companySector = $this->loadData('company_sector');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager           = $this->get('doctrine.orm.entity_manager');

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        $this->sectors    = $companySector->select();

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client') && $this->clients->isBorrower()) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[0]);
            $this->clients_adresses->get($this->clients->id_client, 'id_client');
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            $this->lprojects = $this->projects->select('id_company = "' . $this->companies->id_company . '"');

            if ($this->clients->telephone != '') {
                $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
            }

            $this->bankAccount          = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);
            $this->bankAccountDocuments = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy([
                'idClient' => $client,
                'idType'   => AttachmentType::RIB
            ]);

            if (isset($_POST['form_edit_emprunteur'])) {
                $this->clients->nom    = $this->ficelle->majNom($_POST['nom']);
                $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);

                $checkEmailExistant = $this->clients->select('email = "' . $_POST['email'] . '" AND id_client != ' . $this->clients->id_client);
                if (count($checkEmailExistant) > 0) {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx) {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                } else {
                    $this->clients->email = $_POST['email'];
                }

                $this->clients->telephone       = str_replace(' ', '', $_POST['telephone']);
                $this->companies->name          = $_POST['societe'];
                $this->companies->sector        = isset($_POST['sector']) ? $_POST['sector'] : $this->companies->sector;
                $this->companies->email_facture = trim($_POST['email_facture']);

                if ($this->companies->status_adresse_correspondance == 1) {
                    $this->companies->adresse1 = $_POST['adresse'];
                    $this->companies->city     = $_POST['ville'];
                    $this->companies->zip      = $_POST['cp'];
                }

                $this->clients_adresses->adresse1 = $_POST['adresse'];
                $this->clients_adresses->ville    = $_POST['ville'];
                $this->clients_adresses->cp       = $_POST['cp'];

                $this->companies->update();
                $this->clients->update();
                $this->clients_adresses->update();

                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(6, 'edit emprunteur', $_SESSION['user']['id_user'], $serialize);

                $_SESSION['freeow']['title']   = 'emprunteur mis à jour';
                $_SESSION['freeow']['message'] = 'L\'emprunteur a été mis à jour';

                header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                die;
            }
            $this->aMoneyOrders = $this->clients_mandats->getMoneyOrderHistory($this->companies->id_company);
        } else {
            header('Location: ' . $this->lurl . '/emprunteurs/gestion/');
            die;
        }
    }

    public function _factures()
    {
        $this->hideDecoration();

        $oProject  = $this->loadData('projects');
        $oCompany  = $this->loadData('companies');
        $oClient   = $this->loadData('clients');
        $oInvoices = $this->loadData('factures');

        $oProject->get($this->params[0]);
        $oCompany->get($oProject->id_company);
        $oClient->get($oCompany->id_client_owner);

        $aProjectInvoices = $oInvoices->select('id_project = ' . $oProject->id_project, 'date DESC');

        foreach ($aProjectInvoices as $iKey => $aInvoice) {
            switch ($aInvoice['type_commission']) {
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_FUNDS:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_EF/' . $oClient->hash . '/' . $aInvoice['id_project'];
                    break;
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_REPAYMENT:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_ER/' . $oClient->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                default :
                    trigger_error('Commission type for invoice unknown', E_USER_NOTICE);
                    break;
            }
        }
        $this->aProjectInvoices = $aProjectInvoices;
    }

    public function _link_ligthbox()
    {
        $this->hideDecoration();
        $this->link = '';
        if (false === empty($this->params[0]) && false === empty($this->params[1])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $projectId     = filter_var($this->params[1], FILTER_VALIDATE_INT);
            $project       = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
            if ($project) {
                $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
                switch ($this->params[0]) {
                    case 'pouvoir' :
                        $this->link = $this->furl . '/pdf/pouvoir/' . $client->getHash() . '/' . $projectId;
                        break;
                    case 'mandat' :
                        $this->link = $this->furl . '/pdf/mandat/' . $client->getHash() . '/' . $projectId;
                        break;
                    default :
                        $this->link = '';
                        break;
                }
            }
        }
    }
}
