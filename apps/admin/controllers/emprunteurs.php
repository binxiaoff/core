<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsMandats;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Symfony\Component\HttpFoundation\File\File;

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

    /**
     * @param Clients $client
     */
    private function updateMandat(Clients $client)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $companies     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['idClientOwner' => $client->getIdClient()]);
        foreach ($companies as $company) {
            $projects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $company]);
            foreach ($projects as $project) {
                if (in_array($project->getStatus(), [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE])) {
                    continue;
                }
                $mandates = $project->getMandats();
                if (false === empty($mandates)) {
                    foreach ($mandates as $mandate) {
                        if ($mandate->getStatus() === ClientsMandats::STATUS_ARCHIVED) {
                            continue;
                        }
                        if (ClientsMandats::STATUS_SIGNED == $mandate->getStatus()) {
                            $nouveauNom    = str_replace('mandat', 'mandat-' . $mandate->getIdMandat(), $mandate->getName());
                            $chemin        = $this->path . 'protected/pdf/mandat/' . $mandate->getName();
                            $nouveauChemin = $this->path . 'protected/pdf/mandat/' . $nouveauNom;

                            if (file_exists($chemin)) {
                                rename($chemin, $nouveauChemin);
                            }

                            $mandate->setName($nouveauNom);
                        }
                        $mandate->setStatus(ClientsMandats::STATUS_ARCHIVED);
                        $entityManager->flush($mandate);
                    }
                    // No need to create the new mandat, it will be created in pdf::_mandat()

                    $paymentSchedule = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
                    if (null === $paymentSchedule) {
                        continue;
                    }
                    $monthlyPayment = round(bcdiv($paymentSchedule->getMontant() + $paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);
                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $nextDirectDebit = $entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements')->findOneBy(
                        ['idProject' => $project, 'status' => Prelevements::STATUS_PENDING],
                        ['dateEcheanceEmprunteur' => 'DESC']
                    );

                    if (null === $nextDirectDebit) {
                        continue;
                    }

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->lurl,
                        'prenom_e'               => $client->getPrenom(),
                        'nom_e'                  => $company->getName(),
                        'mensualite'             => $this->ficelle->formatNumber($monthlyPayment),
                        'montant'                => $this->ficelle->formatNumber($project->getAmount(), 0),
                        'link_compte_emprunteur' => $this->lurl . '/projects/detail/' . $project->getIdProject(),
                        'link_mandat'            => $this->furl . '/pdf/mandat/' . $client->getHash() . '/' . $project->getIdProject(),
                        'link_pouvoir'           => $this->furl . '/pdf/pouvoir/' . $client->getHash() . '/' . $project->getIdProject(),
                        'projet'                 => $project->getTitle(),
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw,
                        'date_echeance'          => $nextDirectDebit->getDateEcheanceEmprunteur()->format('d/m/Y')
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('changement-de-rib', $varMail);
                    $message->setTo($client->getEmail());
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }
            }
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
                case \factures::TYPE_COMMISSION_FINANCEMENT :
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_EF/' . $oClient->hash . '/' . $aInvoice['id_project'];
                    break;
                case \factures::TYPE_COMMISSION_REMBOURSEMENT:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_ER/' . $oClient->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                default :
                    trigger_error('Commission type for invoice unknown', E_USER_NOTICE);
                    break;
            }
        }
        $this->aProjectInvoices = $aProjectInvoices;
    }

    public function _extraction_rib_lightbox()
    {
        $this->hideDecoration();

        $this->isImage = false;

        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager    = $this->get('doctrine.orm.entity_manager');
            $this->attachment = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->find($this->params[0]);
            if ($this->attachment) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
                $attachmentManager = $this->get('unilend.service.attachment_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
                $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                try {
                    $file = new File($attachmentManager->getFullPath($this->attachment));
                    if (in_array($file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png', 'image/bmp'])) { // The 4 formats supported by most of the web browser
                        $this->isImage = true;
                    }
                } catch (Exception $exception) {
                    $this->isImage = false;
                }

                if ($this->request->isMethod('POST')) {
                    $iban = $this->request->request->get('iban1')
                        . $this->request->request->get('iban2')
                        . $this->request->request->get('iban3')
                        . $this->request->request->get('iban4')
                        . $this->request->request->get('iban5')
                        . $this->request->request->get('iban6')
                        . $this->request->request->get('iban7');
                    if (trim($iban) && $this->request->request->get('bic')) {
                        try {
                            $bankAccountManager->saveBankInformation($this->attachment->getClient(), $_POST['bic'], $iban, $this->attachment);
                        } catch (Exception $exception) {
                            $_SESSION['freeow']['title']   = 'Erreur RIB';
                            $_SESSION['freeow']['message'] = $exception->getMessage();
                        }
                    }
                    header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->attachment->getClient()->getIdClient());
                    die;
                }
            }
        }
    }

    public function _validate_rib_lightbox()
    {
        $this->hideDecoration();
        if (false === empty($this->params[0])) {
            $entityManager     = $this->get('doctrine.orm.entity_manager');
            $bankAccountId     = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $this->bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($bankAccountId);
        }
    }

    public function _validate_rib()
    {
        if ($this->request->isMethod('POST') && $this->request->request->get('id_bank_account')) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var BankAccount $bankAccount */
            $entityManager->beginTransaction();
            try {
                $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($this->request->request->get('id_bank_account'));
                if ($bankAccount) {
                    $currentBankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($bankAccount->getIdClient());
                    $currentIban        = '';
                    if ($currentBankAccount) {
                        $currentIban = $currentBankAccount->getIban();
                    }
                    $this->updateMandat($bankAccount->getIdClient());
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $oMailerManager */
                    $oMailerManager = $this->get('unilend.service.email_manager');
                    $oMailerManager->sendIbanUpdateToStaff($bankAccount->getIdClient()->getIdClient(), $currentIban, $bankAccount->getIban());

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
                    $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                    $bankAccountManager->validateBankAccount($bankAccount);
                }
                $entityManager->commit();
            } catch (Exception $exception) {
                $entityManager->rollback();
                $_SESSION['freeow']['title']   = 'Erreur RIB';
                $_SESSION['freeow']['message'] = $exception->getMessage();
            }
            header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $bankAccount->getIdClient()->getIdClient());
            die;
        }
        header('Location: ' . $this->lurl);
        die;
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
