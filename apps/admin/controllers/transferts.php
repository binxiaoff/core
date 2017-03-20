<?php

use Psr\Log\LoggerInterface;
use CL\Slack\Payload\ChatPostMessagePayload;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;

class transfertsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('transferts');

        $this->menu_admin = 'transferts';

        $this->statusOperations = array(
            0 => 'Reçu',
            1 => 'Manu',
            2 => 'Auto',
            3 => 'Rejeté',
            4 => 'Rejet'
        );
    }

    public function _default()
    {
        header('Location: /transferts/preteurs');
        die;
    }

    public function _preteurs()
    {
        $this->receptions = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Receptions')->getLenderAttributions();
        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _emprunteurs()
    {
        $this->receptions = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Receptions')->getBorrowerAttributions();
        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _non_attribues()
    {
        $this->aOperations = $this->loadData('receptions')->select('id_client IS NULL AND id_project IS NULL AND type IN (1, 2) AND (type = 1 AND status_prelevement = 2 OR type = 2 AND status_virement = 1)', 'id_reception DESC');

        if (isset($_POST['id_project'], $_POST['id_reception'])) {
            $bank_unilend = $this->loadData('bank_unilend');
            $transactions = $this->loadData('transactions');
            /** @var \Doctrine\ORM\EntityManager $em */
            $em               = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
            $operationManager = $this->get('unilend.service.operation_manager');
            $project          = $em->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']);
            $reception        = $em->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            $client           = $em->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
            $user             = $em->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

            if (null !== $project && null !== $reception) {
                $reception->setIdProject($project)
                          ->setIdClient($client)
                          ->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED)
                          ->setRemb(1)
                          ->setIdUser($user)
                          ->setAssignmentDate(new \DateTime());
                $operationManager->provisionBorrowerWallet($reception);

                if ($_POST['type_remb'] === 'remboursement_anticipe') {
                    $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_EARLY);
                    $transactions->id_virement      = $reception->getIdReception();
                    $transactions->id_project       = $project->getIdProject();
                    $transactions->montant          = $reception->getMontant();
                    $transactions->id_langue        = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = \transactions::STATUS_VALID;
                    $transactions->type_transaction = \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT;
                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $transactions->create();
                } elseif ($_POST['type_remb'] === 'regularisation') {
                    $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_REGULARISATION);
                    $transactions->id_virement      = $reception->getIdReception();
                    $transactions->montant          = $reception->getMontant();
                    $transactions->id_langue        = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = \transactions::STATUS_VALID;
                    $transactions->type_transaction = \transactions_types::TYPE_REGULATION_BANK_TRANSFER;
                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $transactions->create();
                    $this->updateEcheances($project->getIdProject(), $reception->getMontant());
                }

                $em->flush();

                $bank_unilend->id_transaction = $transactions->id_transaction;
                $bank_unilend->id_project     = $project->getIdProject();
                $bank_unilend->montant        = $reception->getMontant();
                $bank_unilend->type           = 1; // remb emprunteur
                $bank_unilend->status         = 0; // chez unilend
                $bank_unilend->create();
            }

            header('Location: ' . $this->lurl . '/transferts/emprunteurs');
            die;
        }
    }

    private function updateEcheances($id_project, $montant)
    {
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers            = $this->loadData('echeanciers');
        $projects_remb          = $this->loadData('projects_remb');

        $eche   = $echeanciers_emprunteur->select('id_project = ' . $id_project . ' AND status_emprunteur = 0', 'ordre ASC');
        $newsum = $montant / 100;

        foreach ($eche as $e) {
            $ordre         = $e['ordre'];
            $montantDuMois = round($e['montant'] / 100 + $e['commission'] / 100 + $e['tva'] / 100, 2);

            if ($montantDuMois <= $newsum) {
                $echeanciers->updateStatusEmprunteur($id_project, $ordre);

                $echeanciers_emprunteur->get($id_project, 'ordre = ' . $ordre . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur             = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                $newsum = $newsum - $montantDuMois;

                if ($projects_remb->counter('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" AND status IN(0, 1)') <= 0) {
                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);

                    $projects_remb->id_project                = $id_project;
                    $projects_remb->ordre                     = $ordre;
                    $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                    $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                    $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                    $projects_remb->status                    = \projects_remb::STATUS_PENDING;
                    $projects_remb->create();
                }
            } else {
                break;
            }
        }
    }

    public function _attribution()
    {
        $this->hideDecoration();

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');
    }

    public function _attribution_preteur()
    {
        $this->hideDecoration();

        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        if (isset($_POST['id'], $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['raison_sociale'], $_POST['id_reception'])) {
            $_SESSION['controlDoubleAttr'] = md5($_SESSION['user']['id_user']);

            $this->lPreteurs    = $this->clients->searchPreteurs($_POST['id'], $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['raison_sociale']);
            $this->id_reception = $_POST['id_reception'];
        }
    }

    public function _attribuer_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \clients $preteurs */
        $preteurs = $this->loadData('clients');
        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \lenders_accounts $lenders */
        $lenders = $this->loadData('lenders_accounts');
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');
        /** @var \notifications notifications */
        $this->notifications = $this->loadData('notifications');
        /** @var \clients_gestion_notifications clients_gestion_notifications */
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif clients_gestion_mails_notif */
        $this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
        $this->loadData('clients_gestion_type_notif'); // Variable is not used but we must call it in order to create CRUD if not existing :'(
        $this->loadData('transactions_types'); // Variable is not used but we must call it in order to create CRUD if not existing :'(
        /** @var \settings setting */
        $this->setting = $this->loadData('settings');

        if (
            isset($_POST['id_client'], $_POST['id_reception'], $_SESSION['controlDoubleAttr'])
            && $_SESSION['controlDoubleAttr'] == md5($_SESSION['user']['id_user'])
        ) {
            unset($_SESSION['controlDoubleAttr']);

            $em = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $reception */
            $reception = $em->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
            $wallet    = $em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($_POST['id_client'], WalletType::LENDER);

            if (null !== $reception && null !== $wallet) {
                $user = $em->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
                $reception->setIdClient($wallet->getIdClient())
                          ->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED)
                          ->setRemb(1)
                          ->setIdUser($user)
                          ->setAssignmentDate(new \DateTime());
                $em->flush();

                $result = $this->get('unilend.service.operation_manager')->provisionLenderWallet($wallet, $reception);

                if ($result) {
                    $this->notifications->type      = \notifications::TYPE_BANK_TRANSFER_CREDIT;
                    $this->notifications->id_lender = $lenders->id_lender_account;
                    $this->notifications->amount    = $receptions->montant;
                    $this->notifications->create();

                    $this->clients_gestion_mails_notif->id_client       = $lenders->id_client_owner;
                    $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
                    $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
                    $this->clients_gestion_mails_notif->create();

                    $preteurs->get($_POST['id_client'], 'id_client');
                    if ($preteurs->etape_inscription_preteur < 3) {
                        $preteurs->etape_inscription_preteur = 3;
                        $preteurs->update();
                    }

                    if ($this->clients_gestion_notifications->getNotif($lenders->id_client_owner, \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement') == true) {
                        $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                        $this->clients_gestion_mails_notif->immediatement = 1;
                        $this->clients_gestion_mails_notif->update();

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $varMail = array(
                            'surl'            => $this->surl,
                            'url'             => $this->furl,
                            'prenom_p'        => html_entity_decode($preteurs->prenom, null, 'UTF-8'),
                            'fonds_depot'     => $this->ficelle->formatNumber($receptions->montant / 100),
                            'solde_p'         => $this->ficelle->formatNumber($transactions->getSolde($receptions->id_client)),
                            'motif_virement'  => $preteurs->getLenderPattern($preteurs->id_client),
                            'projets'         => $this->furl . '/projets-a-financer',
                            'gestion_alertes' => $this->furl . '/profile',
                            'lien_fb'         => $lien_fb,
                            'lien_tw'         => $lien_tw
                        );

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation-manu', $varMail);
                        $message->setTo($preteurs->email);
                        $mailer = $this->get('mailer');
                        $mailer->send($message);
                    }

                    echo $receptions->id_client;
                }
            }
        }
    }

    public function _annuler_attribution_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if (isset($_POST['id_reception'])) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            /** @var Receptions $reception */
            $reception = $em->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            if ($reception) {
                $wallet = $em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::LENDER);
                $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                if ($wallet) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->cancelProvisionLenderWallet($wallet, $amount, $reception);
                    $reception->setIdClient(null)
                              ->setStatusBo(Receptions::STATUS_PENDING)
                              ->setRemb(0); // todo: delete the field
                    $em->flush();
                }
            }
        }
    }

    public function _annuler_attribution_projet()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \projects $projects */
        $projects = $this->loadData('projects');
        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $this->loadData('echeanciers');
        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        /** @var \projects_remb $projects_remb */
        $projects_remb = $this->loadData('projects_remb');

        if ($_POST['id_reception']) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em        = $this->get('doctrine.orm.entity_manager');
            $reception = $em->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            if ($reception) {
                $projectId = $reception->getIdProject()->getIdProject();
                $wallet    = $em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
                if ($wallet) {
                    $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->cancelProvisionBorrowerWallet($wallet, $amount, $reception);

                    $reception->setIdClient(null)
                              ->setIdProject(null)
                              ->setStatusBo(Receptions::STATUS_PENDING)
                              ->setRemb(0); // todo: delete the field
                    $em->flush();

                    $eche   = $echeanciers_emprunteur->select('id_project = ' . $projectId . ' AND status_emprunteur = 1', 'ordre DESC');
                    $newsum = $receptions->montant / 100;

                    foreach ($eche as $e) {
                        $montantDuMois = round($e['montant'] / 100 + $e['commission'] / 100 + $e['tva'] / 100, 2);

                        if ($montantDuMois <= $newsum) {
                            $echeanciers->updateStatusEmprunteur($projectId, $e['ordre'], 'annuler');
                            $echeanciers_emprunteur->get($projectId, 'ordre = ' . $e['ordre'] . ' AND id_project');
                            $echeanciers_emprunteur->status_emprunteur             = 0;
                            $echeanciers_emprunteur->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
                            $echeanciers_emprunteur->update();

                            // et on retire du wallet unilend
                            $newsum = $newsum - $montantDuMois;

                            if ($projects_remb->counter('id_project = "' . $projectId . '" AND ordre = "' . $e['ordre'] . '" AND status = 0') > 0) {
                                $projects_remb->delete($e['ordre'], 'status = 0 AND id_project = "' . $projectId . '" AND ordre');
                            }
                        } else {
                            break;
                        }
                    }
                    echo 'supp';
                    return;
                }
            }
        }
        echo 'nok';
        return;
    }

    public function _rejeter_prelevement_projet()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $this->loadData('echeanciers');
        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        /** @var \projects_remb $projects_remb */
        $projects_remb = $this->loadData('projects_remb');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($_POST['id_reception'])) {
            /** @var Receptions $reception */
            $reception = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            if (null !== $reception && null != $reception->getIdProject() && in_array($reception->getStatusBo(), [Receptions::STATUS_MANUALLY_ASSIGNED, Receptions::STATUS_AUTO_ASSIGNED])) {
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
                if ($wallet) {
                    $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->rejectProvisionBorrowerWallet($wallet, $amount, $reception); //todo: replace it by cancelProvisionBorrowerWallet

                    $reception->setStatusBo(Receptions::STATUS_REJECTED);
                    $reception->setRemb(0);
                    $entityManager->flush();

                    $eche   = $echeanciers_emprunteur->select('id_project = ' . $reception->getIdProject()->getIdProject() . ' AND status_emprunteur = 1', 'ordre DESC');
                    $newsum = $receptions->montant / 100;

                    foreach ($eche as $e) {
                        $montantDuMois = round($e['montant'] / 100 + $e['commission'] / 100 + $e['tva'] / 100, 2);

                        if ($montantDuMois <= $newsum) {
                            $echeanciers->updateStatusEmprunteur($reception->getIdProject()->getIdProject(), $e['ordre'], 'annuler');

                            $echeanciers_emprunteur->get($reception->getIdProject()->getIdProject(), 'ordre = ' . $e['ordre'] . ' AND id_project');
                            $echeanciers_emprunteur->status_emprunteur             = 0;
                            $echeanciers_emprunteur->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
                            $echeanciers_emprunteur->update();

                            // et on retire du wallet unilend
                            $newsum = $newsum - $montantDuMois;

                            // On met a jour le remb emprunteur rejete
                            if ($projects_remb->counter('id_project = "' . $reception->getIdProject()->getIdProject() . '" AND ordre = "' . $e['ordre'] . '" AND status = 0') > 0) {
                                $projects_remb->get($e['ordre'], 'status = 0 AND id_project = "' . $reception->getIdProject()->getIdProject() . '" AND ordre');
                                $projects_remb->status = \projects_remb::STATUS_REJECTED;
                                $projects_remb->update();
                            }
                        } else {
                            break;
                        }
                    }
                    echo 'ok';
                }
            }
        }
    }

    public function _rattrapage_offre_bienvenue()
    {
        /** @var \clients clients */
        $this->clients = $this->loadData('clients');
        /** @var \lenders_accounts $oLendersAccounts */
        $oLendersAccounts = $this->loadData('lenders_accounts');

        unset($_SESSION['forms']['rattrapage_offre_bienvenue']);

        if (isset($_POST['spy_search'])) {
            if (false === empty($_POST['dateStart']) && false === empty($_POST['dateEnd'])) {
                $oDateTimeStart                                                   = \DateTime::createFromFormat('d/m/Y', $_POST['dateStart']);
                $oDateTimeEnd                                                     = \DateTime::createFromFormat('d/m/Y', $_POST['dateEnd']);
                $sStartDateSQL                                                    = $oDateTimeStart->format('Y-m-d');
                $sEndDateSQL                                                      = $oDateTimeEnd->format('Y-m-d');
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sStartDateSQL'] = $sStartDateSQL;
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sEndDateSQL']   = $sEndDateSQL;

                $this->aClientsWithoutWelcomeOffer = $this->clients->getClientsWithNoWelcomeOffer(null, $sStartDateSQL, $sEndDateSQL);
            } elseif (false === empty($_POST['id'])) {
                $this->aClientsWithoutWelcomeOffer = $this->clients->getClientsWithNoWelcomeOffer($_POST['id']);
                $_SESSION['forms']['rattrapage_offre_bienvenue']['id'] = $_POST['id'];
            } else {
                $_SESSION['freeow']['title']   = 'Recherche non aboutie. Indiquez soit la liste des ID clients soit un interval de date';
                $_SESSION['freeow']['message'] = 'Il faut une date de d&eacutebut et de fin ou ID(s)!';
            }
        }

        if (isset($_POST['affect_welcome_offer']) && isset($this->params[0])) {
            if($this->clients->get($this->params[0])&& $oLendersAccounts->get($this->clients->id_client, 'id_client_owner')) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager $welcomeOfferManager */
                $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
                $response = $welcomeOfferManager->createWelcomeOffer($this->clients);

                switch ($response['code']) {
                    case 0:
                        $_SESSION['freeow']['title']   = 'Offre de bienvenue cr&eacute;dit&eacute;';
                        break;
                    default:
                        $_SESSION['freeow']['title']   = 'Offre de bienvenue non cr&eacute;dit&eacute;';
                        break;
                }
                $_SESSION['freeow']['message'] = $response['message'];
            }
        }
    }

    public function _csv_rattrapage_offre_bienvenue()
    {
        $this->autoFireView = false;
        $this->hideDecoration();
        /** @var \clients $oClients */
        $oClients = $this->loadData('clients');
        $aClientsWithoutWelcomeOffer = array();

        if (isset($_SESSION['forms']['rattrapage_offre_bienvenue']['sStartDateSQL']) && isset($_SESSION['forms']['rattrapage_offre_bienvenue']['sEndDateSQL'])) {
            $aClientsWithoutWelcomeOffer = $oClients->getClientsWithNoWelcomeOffer(
                null,
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sStartDateSQL'],
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sEndDateSQL']
            );
        }

        if (isset($_SESSION['forms']['rattrapage_offre_bienvenue']['id'])) {
            $aClientsWithoutWelcomeOffer = $oClients->getClientsWithNoWelcomeOffer($_SESSION['forms']['rattrapage_offre_bienvenue']['id']);
        }

        $sFileName      = 'ratrappage_offre_bienvenue';
        $aColumnHeaders = array('ID Client', 'Nom ou Raison Sociale', 'Prénom', 'Email', 'Date de création', 'Date de validation');
        $aData          = array();

        foreach ($aClientsWithoutWelcomeOffer as $key =>$aClient) {
            $aData[] = array(
                $aClient['id_client'],
                empty($aClient['company']) ? $aClient['nom'] : $aClient['company'],
                empty($aClient['company']) ? $aClient['prenom'] : '',
                $aClient['email'],
                $this->dates->formatDateMysqltoShortFR($aClient['date_creation']),
                (false === empty($aClient['date_validation'])) ? $this->dates->formatDateMysqltoShortFR($aClient['date_validation']) : ''
            );
        }
        $this->exportCSV($aColumnHeaders, $aData, $sFileName);
    }

    private function exportCSV($aColumnHeaders, $aData, $sFileName)
    {
        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (count($aColumnHeaders) > 0) {
            foreach ($aColumnHeaders as $iIndex => $sColumnName) {
                $oActiveSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName);
            }
        }

        foreach ($aData as $iRowIndex => $aRow) {
            $iColIndex = 0;
            foreach ($aRow as $sCellValue) {
                $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $sCellValue);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $sFileName . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        $oWriter->save('php://output');
    }

    public function _affect_welcome_offer()
    {
        $this->hideDecoration();

        $this->oWelcomeOffer = $this->loadData('offres_bienvenues');
        $this->oClient       = $this->loadData('clients');
        $this->oCompany      = $this->loadData('companies');

        $this->oClient->get($this->params[0]);
        $this->oCompany->get('id_client_owner', $this->oClient->id_client);
        $this->oWelcomeOffer->get(1, 'status = 0 AND id_offre_bienvenue');
    }

    public function _deblocage()
    {
        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \clients_mandats $mandate */
        $mandate = $this->loadData('clients_mandats');
        /** @var \projects_pouvoir $proxy */
        $proxy = $this->loadData('projects_pouvoir');
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        if (
            isset($_POST['validateProxy'], $_POST['id_project'])
            && $project->get($_POST['id_project'])
            && $mandate->get($_POST['id_project'] . '" AND status = "' . \clients_mandats::STATUS_SIGNED, 'id_project')
            && $proxy->get($_POST['id_project'] . '" AND status = "' . \projects_pouvoir::STATUS_SIGNED, 'id_project')
        ) {
            /** @var \companies $companies */
            $companies = $this->loadData('companies');
            $companies->get($project->id_company, 'id_company');

            /** @var \clients clients */
            $clients = $this->loadData('clients');
            $clients->get($companies->id_client_owner, 'id_client');

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info('Checking refund status (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));

            /** @var \settings $paymentInspectionStopped */
            $paymentInspectionStopped = $this->loadData('settings');
            $paymentInspectionStopped->get('Controle statut remboursement', 'type');

            if ($project->status != \projects_status::FUNDE) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Le projet n\'est pas fundé';
            } elseif ($paymentInspectionStopped->value == 1) {
                ini_set('memory_limit', '512M');

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $oMailerManager */
                $oMailerManager = $this->get('unilend.service.email_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\NotificationManager $oNotificationManager */
                $oNotificationManager = $this->get('unilend.service.notification_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                $operationManager = $this->get('unilend.service.operation_manager');
                /** @var \lenders_accounts $lender */
                $lender = $this->loadData('lenders_accounts');
                /** @var \transactions $transactions */
                $transactions = $this->loadData('transactions');
                /** @var \loans $loans */
                $loans = $this->loadData('loans');
                /** @var \echeanciers_emprunteur $paymentSchedule */
                $paymentSchedule = $this->loadData('echeanciers_emprunteur');
                /** @var \projects_status_history $projectsStatusHistory */
                $projectsStatusHistory = $this->loadData('projects_status_history');
                /** @var \accepted_bids $acceptedBids */
                $acceptedBids = $this->loadData('accepted_bids');

                $em->getConnection()->beginTransaction();
                try {
                    $proxy->status_remb = \projects_pouvoir::STATUS_VALIDATED;
                    $proxy->update();

                    $paymentInspectionStopped->value = 0;
                    $paymentInspectionStopped->update();

                    $offset = 0;
                    $limit  = 50;
                    $loanRepo = $em->getRepository('UnilendCoreBusinessBundle:Loans');
                    while ($allLoans = $loanRepo->findBy(['idProject' => $_POST['id_project']], null, $limit, $offset)) {
                        foreach ($allLoans as $loan) {
                            $operationManager->loan($loan);
                        }
                        $offset += $limit;
                    }

                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REMBOURSEMENT, $project);

                    if (false === $transactions->get($project->id_project, 'type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . ' AND id_project')) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerManager $borrowerManager */
                        $borrowerManager = $this->get('unilend.service.borrower_manager');

                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $projectEntity */
                        $projectEntity                  = $em->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']);
                        $vatTax                         = $em->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
                        $projectLoanAmount              = $loans->sumPretsProjet($project->id_project);
                        $commissionFundsRateVATIncluded = bcmul(
                            bcadd(1, round(bcdiv($vatTax->getRate(), 100, 4), 2), 2),
                            round(bcdiv($projectEntity->getCommissionRateFunds(), 100, 4), 2),
                            4
                        );
                        $commission                     = round(bcmul($projectLoanAmount, $commissionFundsRateVATIncluded, 4), 2);
                        $operationManager->projectCommission($projectEntity, $commission);
                        $amountToRelease = bcsub($projectLoanAmount, $commission, 2);

                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $borrowerWallet */
                        $borrowerWallet = $em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($companies->id_client_owner, WalletType::BORROWER);
                        //Todo: in MultiRIB, the bank account will be defined in the release funds process.
                        /** @var BankAccount $bankAccount */
                        $bankAccount = $em->getRepository('UnilendCoreBusinessBundle:BankAccount')->findOneBy([
                            'idClient' => $borrowerWallet->getIdClient(),
                            'iban'     => $companies->iban
                        ]);

                        $wireTransferOut = new Virements();
                        $wireTransferOut->setProject($projectEntity)
                                        ->setMotif($borrowerManager->getBorrowerBankTransferLabel($projectEntity))
                                        ->setClient($borrowerWallet->getIdClient())
                                        ->setMontant(bcmul($amountToRelease, 100))
                                        ->setType(Virements::TYPE_BORROWER)
                                        ->setStatus(Virements::STATUS_PENDING)
                                        ->setBankAccount($bankAccount);
                        $em->persist($wireTransferOut);

                        $operationManager->withdrawBorrowerWallet($borrowerWallet, $wireTransferOut, $commission);

                        $aMandate = $mandate->select('id_project = ' . $project->id_project . ' AND id_client = ' . $clients->id_client . ' AND status = ' . \clients_mandats::STATUS_SIGNED, 'id_mandat DESC', 0, 1);
                        $aMandate = array_shift($aMandate);

                        /** @var \prelevements $prelevements */
                        $prelevements = $this->loadData('prelevements');

                        $echea = $paymentSchedule->select('id_project = ' . $project->id_project);

                        foreach ($echea as $key => $e) {
                            $dateEcheEmp = strtotime($e['date_echeance_emprunteur']);
                            $result      = mktime(0, 0, 0, date('m', $dateEcheEmp), date('d', $dateEcheEmp) - 15, date('Y', $dateEcheEmp));

                            $prelevements->id_client                          = $clients->id_client;
                            $prelevements->id_project                         = $project->id_project;
                            $prelevements->motif                              = $wireTransferOut->getMotif();
                            $prelevements->montant                            = bcadd(bcadd($e['montant'], $e['commission'], 2), $e['tva'], 2);
                            $prelevements->bic                                = str_replace(' ', '', $aMandate['bic']);
                            $prelevements->iban                               = str_replace(' ', '', $aMandate['iban']);
                            $prelevements->type_prelevement                   = 1; // recurrent
                            $prelevements->type                               = \prelevements::CLIENT_TYPE_BORROWER; //emprunteur
                            $prelevements->num_prelevement                    = $e['ordre'];
                            $prelevements->date_execution_demande_prelevement = date('Y-m-d', $result);
                            $prelevements->date_echeance_emprunteur           = $e['date_echeance_emprunteur'];
                            $prelevements->create();
                        }

                        $aAcceptedBids = $acceptedBids->getDistinctBids($project->id_project);
                        $aLastLoans    = array();

                        foreach ($aAcceptedBids as $aBid) {
                            $lender->get($aBid['id_lender']);

                            $oNotification = $oNotificationManager->createNotification(\notifications::TYPE_LOAN_ACCEPTED, $lender->id_client_owner, $project->id_project, $aBid['amount'], $aBid['id_bid']);

                            $aLoansForBid = $acceptedBids->select('id_bid = ' . $aBid['id_bid']);

                            foreach ($aLoansForBid as $aLoan) {
                                if (in_array($aLoan['id_loan'], $aLastLoans) === false) {
                                    $oNotificationManager->createEmailNotification($oNotification->id_notification, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, $lender->id_client_owner, null, null, $aLoan['id_loan']);
                                    $aLastLoans[] = $aLoan['id_loan'];
                                }
                            }
                        }

                        $oMailerManager->sendLoanAccepted($project);

                        $oMailerManager->sendBorrowerBill($project);

                        $aRepaymentHistory = $projectsStatusHistory->select('id_project = ' . $project->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added DESC, id_project_status_history DESC', 0, 1);

                        if (false === empty($aRepaymentHistory)) {
                            /** @var \compteur_factures $invoiceCounter */
                            $invoiceCounter = $this->loadData('compteur_factures');
                            /** @var \factures $invoice */
                            $invoice = $this->loadData('factures');

                            $sDateFirstPayment  = $aRepaymentHistory[0]['added'];
                            $fCommission        = bcmul($commission, 100);
                            $fVATFreeCommission = round($fCommission / (1 + $vatTax->getRate() / 100));

                            $invoice->num_facture     = 'FR-E' . date('Ymd', strtotime($sDateFirstPayment)) . str_pad($invoiceCounter->compteurJournalier($project->id_project, $sDateFirstPayment), 5, '0', STR_PAD_LEFT);
                            $invoice->date            = $sDateFirstPayment;
                            $invoice->id_company      = $companies->id_company;
                            $invoice->id_project      = $project->id_project;
                            $invoice->ordre           = 0;
                            $invoice->type_commission = \factures::TYPE_COMMISSION_FINANCEMENT;
                            $invoice->commission      = $project->commission_rate_funds;
                            $invoice->montant_ttc     = $fCommission;
                            $invoice->montant_ht      = $fVATFreeCommission;
                            $invoice->tva             = $fCommission - $fVATFreeCommission;
                            $invoice->create();
                        }

                        $paymentInspectionStopped->value = 1;
                        $paymentInspectionStopped->update();

                        $logger->info('Check refund status done (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));

                        $_SESSION['freeow']['title']   = 'Déblocage des fonds';
                        $_SESSION['freeow']['message'] = 'Le déblocage a été faite avec succès';
                    }
                    $em->flush();
                    $em->getConnection()->commit();
                } catch (\Exception $exception) {
                    $em->getConnection()->rollBack();
                    $logger->error('Release funds failed for project : ' . $project->id_project . '. The process has been rollbacked. Error : ' . $exception->getMessage());

                    $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                    $_SESSION['freeow']['message'] = 'Une erreur s\'élève. Les fonds ne sont pas débloqués';
                }

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Ekomi $ekomi */
                $ekomi = $this->get('unilend.service.ekomi');
                $ekomi->sendProjectEmail($project);

                if ($this->getParameter('kernel.environment') === 'prod') {
                    $payload = new ChatPostMessagePayload();
                    $payload->setChannel('#general');
                    $payload->setText('Fonds débloqués pour *<' . $this->furl . '/projects/detail/' . $project->slug . '|' . $project->title . '>*');
                    $payload->setUsername('Unilend');
                    $payload->setIconUrl($this->get('assets.packages')->getUrl('') . '/assets/images/slack/unilend.png');
                    $payload->setAsUser(false);

                    $this->get('cl_slack.api_client')->send($payload);
                }
            } else {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Un remboursement est déjà en cours';
            }

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->id_project);
            die;
        }

        $aProjects = $project->selectProjectsByStatus([\projects_status::FUNDE], '', [], '', '', false);

        $this->aProjects = array();
        foreach ($aProjects as $index => $aProject) {
            $this->aProjects[$index] = $aProject;

            $aMandate = $mandate->select('id_project = ' . $this->aProjects[$index]['id_project'] . ' AND status = ' . \clients_mandats::STATUS_SIGNED, 'added DESC', 0, 1);
            if ($aMandate = array_shift($aMandate)) {
                $this->aProjects[$index]['bic']           = $aMandate['bic'];
                $this->aProjects[$index]['iban']          = $aMandate['iban'];
                $this->aProjects[$index]['mandat']        = $aMandate['name'];
                $this->aProjects[$index]['status_mandat'] = $aMandate['status'];
            }

            $aProxy = $proxy->select('id_project = ' . $this->aProjects[$index]['id_project'] . ' AND status = ' . \projects_pouvoir::STATUS_SIGNED, 'added DESC', 0, 1);
            if ($aProxy = array_shift($aProxy)) {
                $this->aProjects[$index]['url_pdf']          = $aProxy['name'];
                $this->aProjects[$index]['status_remb']      = $aProxy['status_remb'];
                $this->aProjects[$index]['authority_status'] = $aProxy['status'];
            }

            $projectEntity                      = $em->getRepository('UnilendCoreBusinessBundle:Projects')->find($aProject['id_project']);
            $projectAttachments                 = $projectEntity->getAttachments();
            $this->aProjects[$index]['kbis']    = '';
            $this->aProjects[$index]['id_kbis'] = '';
            $this->aProjects[$index]['rib']     = '';
            $this->aProjects[$index]['id_rib']  = '';
            foreach ($projectAttachments as $projectAttachment) {
                $attachment = $projectAttachment->getAttachment();
                if (AttachmentType::KBIS === $attachment->getType()->getId()) {
                    $this->aProjects[$index]['kbis']    = $attachment->getPath();
                    $this->aProjects[$index]['id_kbis'] = $attachment->getId();
                }
                if (AttachmentType::RIB === $attachment->getType()->getId()) {
                    $this->aProjects[$index]['rib']    = $attachment->getPath();
                    $this->aProjects[$index]['id_rib'] = $attachment->getId();
                }
            }
        }
    }

    public function _succession()
    {
        if (isset($_POST['succession_check']) || isset($_POST['succession_validate'])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientManager $clientManager */
            $clientManager = $this->get('unilend.service.client_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
            $clientStatusManager = $this->get('unilend.service.client_status_manager');
            /** @var \clients $originalClient */
            $originalClient = $this->loadData('clients');
            /** @var \clients $newOwner */
            $newOwner = $this->loadData('clients');

            if (
                false === empty($_POST['id_client_to_transfer'])
                && (false === is_numeric($_POST['id_client_to_transfer'])
                    || false === $originalClient->get($_POST['id_client_to_transfer'])
                    || false === $clientManager->isLender($originalClient))) {
                $this->addErrorMessageAndRedirect('Le défunt n\'est pas un prêteur');
            }

            if (
                false === empty($_POST['id_client_receiver'])
                && (false === is_numeric($_POST['id_client_receiver'])
                    || false === $newOwner->get($_POST['id_client_receiver'])
                    || false === $clientManager->isLender($newOwner))
            ) {
                $this->addErrorMessageAndRedirect('L\'héritier n\'est pas un prêteur');
            }

            /** @var \lenders_accounts $originalLender */
            $originalLender = $this->loadData('lenders_accounts');
            $originalLender->get($originalClient->id_client, 'id_client_owner');

            if ($clientStatusManager->getLastClientStatus($newOwner) != \clients_status::VALIDATED) {
                $this->addErrorMessageAndRedirect('Le compte de l\'héritier n\'est pas validé');
            }

            /** @var \bids $bids */
            $bids = $this->loadData('bids');
            if ($bids->exist($originalLender->id_lender_account, 'status = ' . \bids::STATUS_BID_PENDING . ' AND id_lender_account ')) {
                $this->addErrorMessageAndRedirect('Le défunt a des bids en cours.');
            }

            /** @var \loans $loans */
            $loans                 = $this->loadData('loans');
            $loansInRepayment      = $loans->getLoansForProjectsWithStatus($originalLender->id_lender_account, array_merge(\projects_status::$runningRepayment, [\projects_status::FUNDE]));
            $originalClientBalance = $clientManager->getClientBalance($originalClient);

            if (isset($_POST['succession_check'])) {
                $_SESSION['succession']['check'] = [
                    'accountBalance' => $originalClientBalance,
                    'numberLoans'    => count($loansInRepayment),
                    'formerClient'   => [
                        'nom'       => $originalClient->nom,
                        'prenom'    => $originalClient->prenom,
                        'id_client' => $originalClient->id_client
                    ],
                    'newOwner'       => [
                        'nom'       => $newOwner->nom,
                        'prenom'    => $newOwner->prenom,
                        'id_client' => $newOwner->id_client
                    ]
                ];
            }

            if (isset($_POST['succession_validate'])) {
                $transferDocument = $this->request->files->get('transfer_document');
                if (null === $transferDocument) {
                    $this->addErrorMessageAndRedirect('Il manque le justificatif de transfer');
                }
                /** @var \Doctrine\ORM\EntityManager $em */
                $em = $this->get('doctrine.orm.entity_manager');

                $em->getConnection()->beginTransaction();
                try {
                    /** @var \transfer $transfer */
                    $transfer                     = $this->loadData('transfer');
                    $transfer->id_client_origin   = $originalClient->id_client;
                    $transfer->id_client_receiver = $newOwner->id_client;
                    $transfer->id_transfer_type   = \transfer_type::TYPE_INHERITANCE;
                    $transfer->create();

                    $transferEntity = $em->getRepository('UnilendCoreBusinessBundle:Transfer')->find($transfer->id_transfer);
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
                    $attachmentManager = $this->get('unilend.service.attachment_manager');
                    $attachmentType    = $em->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::TRANSFER_CERTIFICATE);
                    if ($attachmentType) {
                        $attachment = $attachmentManager->upload($transferEntity->getClientReceiver(), $attachmentType, $transferDocument);
                    }
                    if (false === empty($attachment)) {
                        $attachmentManager->attachToTransfer($attachment, $transferEntity);
                    }
                    $originalClientBalance = $clientManager->getClientBalance($originalClient);
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->lenderTransfer($transferEntity, $originalClientBalance);

                    /** @var \loan_transfer $loanTransfer */
                    $loanTransfer = $this->loadData('loan_transfer');
                    /** @var \lenders_accounts $originalLender */
                    $originalLender = $this->loadData('lenders_accounts');
                    $originalLender->get($transfer->id_client_origin, 'id_client_owner');
                    /** @var \lenders_accounts $newLender */
                    $newLender = $this->loadData('lenders_accounts');
                    $newLender->get($transfer->id_client_receiver, 'id_client_owner');

                    $numberLoans = 0;
                    foreach ($loansInRepayment as $loan) {
                        $loans->get($loan['id_loan']);
                        $this->transferLoan($transfer, $loanTransfer, $loans, $newLender, $originalClient, $newOwner);
                        $loans->unsetData();
                        $numberLoans += 1;
                    }
                    /** @var \lenders_accounts_stats_queue $lenderStatQueue */
                    $lenderStatQueue = $this->loadData('lenders_accounts_stats_queue');
                    $lenderStatQueue->addLenderToQueue($newLender);
                    $lenderStatQueue->addLenderToQueue($originalLender);

                    $comment = 'Compte soldé . ' . $this->ficelle->formatNumber($originalClientBalance) . ' EUR et ' . $numberLoans . ' prêts transferés sur le compte client ' . $newOwner->id_client;
                    try {
                        $clientStatusManager->closeAccount($originalClient, $_SESSION['user']['id_user'], $comment);
                    } catch (\Exception $exception) {
                        $this->addErrorMessageAndRedirect('Le status client n\'a pas pu être changé ' . $exception->getMessage());
                    }

                    $clientStatusManager->addClientStatus($newOwner, $_SESSION['user']['id_user'], $clientStatusManager->getLastClientStatus($newOwner),
                        'Reçu solde (' . $this->ficelle->formatNumber($originalClientBalance) . ') et prêts (' . $numberLoans . ') du compte ' . $originalClient->id_client);

                    $em->getConnection()->commit();
                } catch (Exception $exception) {
                    $em->getConnection()->rollback();
                    throw $exception;
                }
                $_SESSION['succession']['success'] = [
                    'accountBalance' => $originalClientBalance,
                    'numberLoans'    => $numberLoans,
                    'formerClient'   => [
                        'nom'    => $originalClient->nom,
                        'prenom' => $originalClient->prenom
                    ],
                    'newOwner'       => [
                        'nom'    => $newOwner->nom,
                        'prenom' => $newOwner->prenom
                    ]
                ];
            }

            header('Location: ' . $this->lurl . '/transferts/succession');
            die;
        }
    }

    private function transferLoan(\transfer $transfer, \loan_transfer $loanTransfer, \loans $loans, \lenders_accounts $newLender, \clients $originalClient, \clients $newOwner)
    {
        $loanTransfer->id_transfer = $transfer->id_transfer;
        $loanTransfer->id_loan     = $loans->id_loan;
        $loanTransfer->create();

        $loans->id_transfer = $loanTransfer->id_loan_transfer;
        $loans->id_lender   = $newLender->id_lender_account;
        $loans->update();

        $loanTransfer->unsetData();
        $this->transferRepaymentSchedule($loans, $newLender);
        $this->transferLoanPdf($loans, $originalClient, $newOwner);
        $this->deleteClaimsPdf($loans, $originalClient);
    }

    /**
     * @param \loans $loans
     * @param \lenders_accounts $newLender
     */
    private function transferRepaymentSchedule(\loans $loans, \lenders_accounts $newLender)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->loadData('echeanciers');

        foreach ($repaymentSchedule->select('id_loan = ' . $loans->id_loan) as $repayment){
            $repaymentSchedule->get($repayment['id_echeancier']);
            $repaymentSchedule->id_lender = $newLender->id_lender_account;
            $repaymentSchedule->update();
            $repaymentSchedule->unsetData();
        }
    }

    /**
     * @param string $errorMessage
     */
    private function addErrorMessageAndRedirect($errorMessage)
    {
        $_SESSION['succession']['error'] = $errorMessage;
        header('Location: ' . $this->lurl . '/transferts/succession');
        die;
    }

    private function transferLoanPdf(\loans $loan, \clients $originalClient, \clients $newOwner)
    {
        $oldFilePath = $this->path . 'protected/pdf/contrat/contrat-' . $originalClient->hash . '-' . $loan->id_loan . '.pdf';
        $newFilePath = $this->path . 'protected/pdf/contrat/contrat-' . $newOwner->hash . '-' . $loan->id_loan . '.pdf';

        if (file_exists($oldFilePath)) {
            rename($oldFilePath, $newFilePath);
        }
    }

    private function deleteClaimsPdf(\loans $loan, \clients $originalClient)
    {
        $filePath      = $this->path . 'protected/pdf/declaration_de_creances/' . $loan->id_project . '/';
        $filePath      = ($loan->id_project == '1456') ? $filePath : $filePath . $originalClient->id_client . '/';
        $filePath      = $filePath . 'declaration-de-creances' . '-' . $originalClient->hash . '-' . $loan->id_loan . '.pdf';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
