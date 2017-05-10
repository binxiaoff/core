<?php

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatisticQueue;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

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
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
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
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->nonAttributedReceptions = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findNonAttributed();

        if (isset($_POST['id_project'], $_POST['id_reception'])) {
            $bank_unilend = $this->loadData('bank_unilend');
            $transactions = $this->loadData('transactions');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
            $operationManager = $this->get('unilend.service.operation_manager');
            $project          = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']);
            $reception        = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            $client           = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
            $user             = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

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

                $entityManager->flush();

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
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $reception */
            $reception = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($_POST['id_client'], WalletType::LENDER);

            if (null !== $reception && null !== $wallet) {
                $user  = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

                $reception->setIdClient($wallet->getIdClient())
                          ->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED)
                          ->setRemb(1)
                          ->setIdUser($user)
                          ->setAssignmentDate(new \DateTime());
                $entityManager->flush();

                $result = $this->get('unilend.service.operation_manager')->provisionLenderWallet($wallet, $reception);

                if ($result) {
                    $this->notifications->type      = \notifications::TYPE_BANK_TRANSFER_CREDIT;
                    $this->notifications->id_lender = $wallet->getId();
                    $this->notifications->amount    = $reception->getMontant();
                    $this->notifications->create();

                    $this->clients_gestion_mails_notif->id_client       = $wallet->getIdClient()->getIdClient();
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

                    if ($this->clients_gestion_notifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement') == true) {
                        $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                        $this->clients_gestion_mails_notif->immediatement = 1;
                        $this->clients_gestion_mails_notif->update();

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $varMail = [
                            'surl'            => $this->surl,
                            'url'             => $this->furl,
                            'prenom_p'        => html_entity_decode($preteurs->prenom, null, 'UTF-8'),
                            'fonds_depot'     => $this->ficelle->formatNumber($reception->getMontant() / 100),
                            'solde_p'         => $this->ficelle->formatNumber($wallet->getAvailableBalance()),
                            'motif_virement'  => $wallet->getWireTransferPattern(),
                            'projets'         => $this->furl . '/projets-a-financer',
                            'gestion_alertes' => $this->furl . '/profile',
                            'lien_fb'         => $lien_fb,
                            'lien_tw'         => $lien_tw
                        ];

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation-manu', $varMail);
                        $message->setTo($preteurs->email);
                        $mailer = $this->get('mailer');
                        $mailer->send($message);
                    }

                    echo $reception->getIdClient()->getIdClient();
                }
            }
        }
    }

    public function _annuler_attribution_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if (isset($_POST['id_reception'])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var Receptions $reception */
            $reception = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            if ($reception) {
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::LENDER);
                $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                if ($wallet) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->cancelProvisionLenderWallet($wallet, $amount, $reception);
                    $reception->setIdClient(null)
                              ->setStatusBo(Receptions::STATUS_PENDING)
                              ->setRemb(0); // todo: delete the field
                    $entityManager->flush();
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
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $reception     = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            if ($reception) {
                $projectId = $reception->getIdProject()->getIdProject();
                $wallet    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
                if ($wallet) {
                    $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->cancelProvisionBorrowerWallet($wallet, $amount, $reception);

                    $reception->setIdClient(null)
                              ->setIdProject(null)
                              ->setStatusBo(Receptions::STATUS_PENDING)
                              ->setRemb(0); // todo: delete the field
                    $entityManager->flush();

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
                $this->aClientsWithoutWelcomeOffer                     = $this->clients->getClientsWithNoWelcomeOffer($_POST['id']);
                $_SESSION['forms']['rattrapage_offre_bienvenue']['id'] = $_POST['id'];
            } else {
                $_SESSION['freeow']['title']   = 'Recherche non aboutie. Indiquez soit la liste des ID clients soit un interval de date';
                $_SESSION['freeow']['message'] = 'Il faut une date de d&eacutebut et de fin ou ID(s)!';
            }
        }

        if (isset($_POST['affect_welcome_offer']) && isset($this->params[0])&& is_numeric($this->params[0])) {
            if($this->clients->get($this->params[0])) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager $welcomeOfferManager */
                $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
                $response            = $welcomeOfferManager->createWelcomeOffer($this->clients);

                switch ($response['code']) {
                    case 0:
                        $_SESSION['freeow']['title'] = 'Offre de bienvenue cr&eacute;dit&eacute;';
                        break;
                    default:
                        $_SESSION['freeow']['title'] = 'Offre de bienvenue non cr&eacute;dit&eacute;';
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
        $oClients                    = $this->loadData('clients');
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

        foreach ($aClientsWithoutWelcomeOffer as $key => $aClient) {
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

    public function _deblocage() //TODO delete lenders_accounts after merge of transfer progressif
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($_POST['validateProxy'], $_POST['id_project'])) {
            $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']);
            if (null === $project) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Le projet ' . $_POST['id_project'] . 'n\'existe pas';
                header('Location: ' . $this->lurl . '/transferts/deblocage/');
                die;
            }
            if (null === $project->getIdCompany()) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'La société du project ' . $_POST['id_project'] . 'n\'existe pas';
                header('Location: ' . $this->lurl . '/transferts/deblocage/');
                die;
            }
            $mandate = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findOneBy([
                'idProject' => $_POST['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);
            $proxy   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->findOneBy([
                'idProject' => $_POST['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);

            if (null === $mandate || null === $proxy) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Le mandat ou pouvoir non signé pour le project ' . $_POST['id_project'];
                header('Location: ' . $this->lurl . '/transferts/deblocage/');
                die;
            }

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info('Checking refund status (project ' . $project->getIdProject() . ')', ['class' => __CLASS__, 'function' => __FUNCTION__]);

            /** @var \settings $paymentInspectionStopped */
            $paymentInspectionStopped = $this->loadData('settings');
            $paymentInspectionStopped->get('Controle statut remboursement', 'type');

            if (1 != $paymentInspectionStopped->value) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Un remboursement est déjà en cours';
                header('Location: ' . $this->lurl . '/transferts/deblocage/');
                die;
            }

            if ($project->getStatus() != ProjectsStatus::FUNDE) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Le projet n\'est pas fundé';
                header('Location: ' . $this->lurl . '/transferts/deblocage/');
                die;
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\NotificationManager $notificationManager */
            $notificationManager = $this->get('unilend.service.notification_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
            $operationManager = $this->get('unilend.service.operation_manager');
            /** @var \lenders_accounts $lender */
            $lender = $this->loadData('lenders_accounts');
            /** @var \echeanciers_emprunteur $paymentSchedule */
            $paymentSchedule = $this->loadData('echeanciers_emprunteur');
            /** @var \projects_status_history $projectsStatusHistory */
            $projectsStatusHistory = $this->loadData('projects_status_history');
            /** @var \accepted_bids $acceptedBids */
            $acceptedBids = $this->loadData('accepted_bids');

            $entityManager->getConnection()->beginTransaction();
            try {
                $paymentInspectionStopped->value = 0;
                $paymentInspectionStopped->update();

                $proxy->setStatusRemb(ProjectsPouvoir::STATUS_REPAYMENT_VALIDATED);
                $entityManager->flush($proxy);

                $offset         = 0;
                $limit          = 50;
                $loanRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
                while ($allLoans = $loanRepository->findBy(['idProject' => $project], null, $limit, $offset)) {
                    foreach ($allLoans as $loan) {
                        $operationManager->loan($loan);
                    }
                    $offset += $limit;
                }

                $commission = $projectManager->getCommissionFunds($project, true);
                $operationManager->projectCommission($project, $commission);

                $projectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REMBOURSEMENT, $project);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerManager $borrowerManager */
                $borrowerManager = $this->get('unilend.service.borrower_manager');

                /** @var \prelevements $prelevements */
                $prelevements = $this->loadData('prelevements');

                $echea = $paymentSchedule->select('id_project = ' . $project->getIdProject());

                foreach ($echea as $key => $e) {
                    $dateEcheEmp = strtotime($e['date_echeance_emprunteur']);
                    $result      = mktime(0, 0, 0, date('m', $dateEcheEmp), date('d', $dateEcheEmp) - 15, date('Y', $dateEcheEmp));

                    $prelevements->id_client                          = $project->getIdCompany()->getIdClientOwner();
                    $prelevements->id_project                         = $project->getIdProject();
                    $prelevements->motif                              = $borrowerManager->getBorrowerBankTransferLabel($project);
                    $prelevements->montant                            = bcadd(bcadd($e['montant'], $e['commission'], 2), $e['tva'], 2);
                    $prelevements->bic                                = str_replace(' ', '', $mandate->getBic());
                    $prelevements->iban                               = str_replace(' ', '', $mandate->getIban());
                    $prelevements->type_prelevement                   = 1; // recurrent
                    $prelevements->type                               = \prelevements::CLIENT_TYPE_BORROWER;
                    $prelevements->num_prelevement                    = $e['ordre'];
                    $prelevements->date_execution_demande_prelevement = date('Y-m-d', $result);
                    $prelevements->date_echeance_emprunteur           = $e['date_echeance_emprunteur'];
                    $prelevements->create();
                }

                $allAcceptedBids = $acceptedBids->getDistinctBids($project->getIdProject());
                $lastLoans       = array();

                foreach ($allAcceptedBids as $bid) {
                    $lender->get($bid['id_lender']);

                    $notification = $notificationManager->createNotification(\notifications::TYPE_LOAN_ACCEPTED, $lender->id_client_owner, $project->getIdProject(), $bid['amount'],
                        $bid['id_bid']);

                    $loansForBid = $acceptedBids->select('id_bid = ' . $bid['id_bid']);

                    foreach ($loansForBid as $loan) {
                        if (in_array($loan['id_loan'], $lastLoans) === false) {
                            $notificationManager->createEmailNotification($notification->id_notification, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, $lender->id_client_owner, null, null,
                                $loan['id_loan']);
                            $lastLoans[] = $loan['id_loan'];
                        }
                    }
                }
                $mailerManager->sendLoanAccepted($project);
                $mailerManager->sendBorrowerBill($project);

                $repaymentHistory = $projectsStatusHistory->select('id_project = ' . $project->getIdProject() . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')',
                    'added DESC, id_project_status_history DESC', 0, 1);

                if (false === empty($repaymentHistory)) {
                    /** @var \compteur_factures $invoiceCounter */
                    $invoiceCounter = $this->loadData('compteur_factures');
                    /** @var \factures $invoice */
                    $invoice = $this->loadData('factures');

                    $dateFirstPayment         = $repaymentHistory[0]['added'];
                    $commissionIncents        = bcmul($commission, 100);
                    $commissionIncentsExclTax = bcmul($projectManager->getCommissionFunds($project, false), 100);

                    $invoice->num_facture     = 'FR-E' . date('Ymd', strtotime($dateFirstPayment)) . str_pad($invoiceCounter->compteurJournalier($project->getIdProject(), $dateFirstPayment), 5, '0',
                            STR_PAD_LEFT);
                    $invoice->date            = $dateFirstPayment;
                    $invoice->id_company      = $project->getIdCompany()->getIdCompany();
                    $invoice->id_project      = $project->getIdProject();
                    $invoice->ordre           = 0;
                    $invoice->type_commission = \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_FUNDS;
                    $invoice->commission      = $project->getCommissionRateFunds();
                    $invoice->montant_ttc     = $commissionIncents;
                    $invoice->montant_ht      = $commissionIncentsExclTax;
                    $invoice->tva             = $commissionIncents - $commissionIncentsExclTax;
                    $invoice->create();
                }

                $paymentInspectionStopped->value = 1;
                $paymentInspectionStopped->update();

                $logger->info('Check refund status done (project ' . $project->getIdProject() . ')', ['class' => __CLASS__, 'function' => __FUNCTION__]);

                $_SESSION['freeow']['title']   = 'Déblocage des fonds';
                $_SESSION['freeow']['message'] = 'Le déblocage a été fait avec succès';

                $entityManager->flush();
                $entityManager->getConnection()->commit();

                try {
                    if ($this->getParameter('kernel.environment') === 'prod') {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Ekomi $ekomi */
                        $ekomi = $this->get('unilend.service.ekomi');
                        $ekomi->sendProjectEmail($project);
                    }

                    $slackManager = $this->container->get('unilend.service.slack_manager');
                    $message      = $slackManager->getProjectName($project) . ' - Fonds débloqués par ' . $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'];
                    $slackManager->sendMessage($message);
                } catch (\Exception $exception) {
                    // Nothing to do, but it must not disturb the DB transaction.
                }

            } catch (\Exception $exception) {
                $entityManager->getConnection()->rollBack();
                $logger->error('Release funds failed for project : ' . $project->getIdProject() . '. The process has been rollbacked. Error : ' . $exception->getMessage());

                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Une erreur s\'élève. Les fonds ne sont pas débloqués';
            }

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
            die;
        }
        /** @var projects $projectData */
        $projectData = $this->loadData('projects');
        $aProjects   = $projectData->selectProjectsByStatus([\projects_status::FUNDE], '', [], '', '', false);

        $this->aProjects = [];
        foreach ($aProjects as $index => $aProject) {
            $this->aProjects[$index] = $aProject;

            $mandate = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findOneBy([
                'idProject' => $aProject['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);
            $proxy   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->findOneBy([
                'idProject' => $aProject['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);

            if ($mandate) {
                $this->aProjects[$index]['bic']           = $mandate->getBic();
                $this->aProjects[$index]['iban']          = $mandate->getIban();
                $this->aProjects[$index]['mandat']        = $mandate->getName();
                $this->aProjects[$index]['status_mandat'] = $mandate->getStatus();
            }

            if ($proxy) {
                $this->aProjects[$index]['url_pdf']          = $proxy->getName();
                $this->aProjects[$index]['status_remb']      = $proxy->getStatusRemb();
                $this->aProjects[$index]['authority_status'] = $proxy->getStatus();
            }

            $project                            = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($aProject['id_project']);
            $projectAttachments                 = $project->getAttachments();
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
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository $walletRepository */
            $walletRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

            if (
                false === empty($_POST['id_client_to_transfer'])
                && (false === is_numeric($_POST['id_client_to_transfer'])
                    || false === $originalClient->get($_POST['id_client_to_transfer'])
                    || false === $clientManager->isLender($originalClient))
            ) {
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

            if ($clientStatusManager->getLastClientStatus($newOwner) != \clients_status::VALIDATED) {
                $this->addErrorMessageAndRedirect('Le compte de l\'héritier n\'est pas validé');
            }

            /** @var \bids $bids */
            $bids           = $this->loadData('bids');
            $originalWallet = $walletRepository->getWalletByType($originalClient->id_client, WalletType::LENDER);
            if ($bids->exist($originalWallet->getId(), 'status = ' . \bids::STATUS_BID_PENDING . ' AND id_lender_account ')) {
                $this->addErrorMessageAndRedirect('Le défunt a des bids en cours.');
            }

            /** @var \loans $loans */
            $loans                 = $this->loadData('loans');
            $loansInRepayment      = $loans->getLoansForProjectsWithStatus($originalWallet->getId(), array_merge(\projects_status::$runningRepayment, [\projects_status::FUNDE]));
            $originalClientBalance = $originalWallet->getAvailableBalance();

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

                $entityManager->getConnection()->beginTransaction();
                try {
                    /** @var \transfer $transfer */
                    $transfer                     = $this->loadData('transfer');
                    $transfer->id_client_origin   = $originalClient->id_client;
                    $transfer->id_client_receiver = $newOwner->id_client;
                    $transfer->id_transfer_type   = \transfer_type::TYPE_INHERITANCE;
                    $transfer->create();

                    $transferEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Transfer')->find($transfer->id_transfer);
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
                    $attachmentManager = $this->get('unilend.service.attachment_manager');
                    $attachmentType    = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::TRANSFER_CERTIFICATE);
                    if ($attachmentType) {
                        $attachment = $attachmentManager->upload($transferEntity->getClientReceiver(), $attachmentType, $transferDocument);
                    }
                    if (false === empty($attachment)) {
                        $attachmentManager->attachToTransfer($attachment, $transferEntity);
                    }
                    $originalClientBalance = $originalWallet->getAvailableBalance();
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->lenderTransfer($transferEntity, $originalClientBalance);

                    /** @var \loan_transfer $loanTransfer */
                    $loanTransfer = $this->loadData('loan_transfer');
                    $newWallet    = $walletRepository->getWalletByType($transfer->id_client_receiver, WalletType::LENDER);

                    $numberLoans = 0;
                    foreach ($loansInRepayment as $loan) {
                        $loans->get($loan['id_loan']);
                        $this->transferLoan($transfer, $loanTransfer, $loans, $newWallet, $originalClient, $newOwner);
                        $loans->unsetData();
                        $numberLoans += 1;
                    }

                    $lenderStatQueueOriginal = new LenderStatisticQueue();
                    $lenderStatQueueOriginal->setIdWallet($originalWallet);
                    $entityManager->persist($lenderStatQueueOriginal);
                    $lenderStatQueueNew = new LenderStatisticQueue();
                    $lenderStatQueueNew->setIdWallet($newWallet);
                    $entityManager->persist($lenderStatQueueNew);
                    $entityManager->flush();

                    $comment = 'Compte soldé . ' . $this->ficelle->formatNumber($originalClientBalance) . ' EUR et ' . $numberLoans . ' prêts transferés sur le compte client ' . $newOwner->id_client;
                    try {
                        $clientStatusManager->closeAccount($originalClient, $_SESSION['user']['id_user'], $comment);
                    } catch (\Exception $exception) {
                        $this->addErrorMessageAndRedirect('Le status client n\'a pas pu être changé ' . $exception->getMessage());
                        throw $exception;
                    }

                    $clientStatusManager->addClientStatus($newOwner, $_SESSION['user']['id_user'], $clientStatusManager->getLastClientStatus($newOwner),
                        'Reçu solde (' . $this->ficelle->formatNumber($originalClientBalance) . ') et prêts (' . $numberLoans . ') du compte ' . $originalClient->id_client);

                    $entityManager->getConnection()->commit();
                } catch (\Exception $exception) {
                    $entityManager->getConnection()->rollback();
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

    /**
     * @param \transfer      $transfer
     * @param \loan_transfer $loanTransfer
     * @param \loans         $loans
     * @param Wallet         $newLender
     * @param \clients       $originalClient
     * @param \clients       $newOwner
     */
    private function transferLoan(\transfer $transfer, \loan_transfer $loanTransfer, \loans $loans, Wallet $newLender, \clients $originalClient, \clients $newOwner)
    {
        $loanTransfer->id_transfer = $transfer->id_transfer;
        $loanTransfer->id_loan     = $loans->id_loan;
        $loanTransfer->create();

        $loans->id_transfer = $loanTransfer->id_loan_transfer;
        $loans->id_lender   = $newLender->getId();
        $loans->update();

        $loanTransfer->unsetData();
        $this->transferRepaymentSchedule($loans, $newLender);
        $this->transferLoanPdf($loans, $originalClient, $newOwner);
        $this->deleteClaimsPdf($loans, $originalClient);
    }

    /**
     * @param \loans            $loans
     * @param Wallet $newLender
     */
    private function transferRepaymentSchedule(\loans $loans, Wallet $newLender)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->loadData('echeanciers');

        foreach ($repaymentSchedule->select('id_loan = ' . $loans->id_loan) as $repayment) {
            $repaymentSchedule->get($repayment['id_echeancier']);
            $repaymentSchedule->id_lender = $newLender->getId();
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
        $filePath = $this->path . 'protected/pdf/declaration_de_creances/' . $loan->id_project . '/';
        $filePath = ($loan->id_project == '1456') ? $filePath : $filePath . $originalClient->id_client . '/';
        $filePath = $filePath . 'declaration-de-creances' . '-' . $originalClient->hash . '-' . $loan->id_loan . '.pdf';

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function _add_lightbox()
    {
        $this->hideDecoration();

        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerManager $borrowerManager */
            $borrowerManager = $this->get('unilend.service.borrower_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\PartnerManager $partnerManager */
            $partnerManager = $this->get('unilend.service.partner_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WireTransferOutManager $wireTransferOutManager */
            $wireTransferOutManager = $this->get('unilend.service.wire_transfer_out_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            /** @var \NumberFormatter $currencyFormatter */
            $currencyFormatter = $this->get('currency_formatter');

            $this->companyRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
            $this->project           = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->params[0]);
            $this->borrowerMotif     = $borrowerManager->getBorrowerBankTransferLabel($this->project);
            $this->bankAccounts[]    = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->project->getIdCompany()->getIdClientOwner());
            $this->bankAccounts      = array_merge($this->bankAccounts, $partnerManager->getPartnerThirdPartyBankAccounts($this->project->getPartner()));
            $restFunds               = $projectManager->getRestOfFundsToRelease($this->project, true);
            $this->restFunds         = $currencyFormatter->formatCurrency($restFunds, 'EUR');

            if ($this->request->isMethod('POST')) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                $projectManager = $this->get('unilend.service.project_manager');

                if ($this->request->request->get('date')) {
                    $date = DateTime::createFromFormat('d/m/Y', $this->request->request->get('date'));
                } else {
                    $date = null;
                }
                $amount = $this->loadLib('ficelle')->cleanFormatedNumber($this->request->request->get('amount'));

                if ($amount <= 0) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Montant n\'est pas valide.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $restFunds = $projectManager->getRestOfFundsToRelease($this->project, true);
                if ($amount > $restFunds) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Montant trop élévé.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($this->request->request->get('bank_account'));
                $wallet      = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
                $user        = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

                try {
                    $wireTransferOutManager->createTransfer($wallet, $amount, $bankAccount, $this->project, $user, $date, $this->request->request->get('pattern'));
                } catch (\Exception $exception) {
                    $this->get('logger')->error($exception->getMessage(), ['methode' => __METHOD__]);
                    $_SESSION['freeow']['title']   = 'Transfert de fonds échoué';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé';

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $_SESSION['freeow']['title']   = 'Transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds a été créé avec succès ';
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                die;
            }
        }
    }

    public function _refuse_lightbox()
    {
        $wireTransferOut = $this->prepareDisplayWireTransferOut();
        if (false === empty($this->params[0]) && $this->request->isMethod('POST') && $wireTransferOut) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager   = $this->get('doctrine.orm.entity_manager');

            $forbiddenStatus = [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED, Virements::STATUS_VALIDATED, Virements::STATUS_SENT];
            if (false === in_array($wireTransferOut->getStatus(), $forbiddenStatus)) {
                $wireTransferOut->setStatus(Virements::STATUS_DENIED);
                $entityManager->flush($wireTransferOut);
                $_SESSION['freeow']['title']   = 'Refus de transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds a été refusé avec succès ';
            } else {
                $_SESSION['freeow']['title']   = 'Refus de transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a été refusé.';
            }
            if (false === empty($this->params[1]) && 'project' === $this->params[1]) {
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $wireTransferOut->getProject()->getIdProject());
            } else {
                header('Location: ' . $this->lurl . '/transferts/virement_emprunteur');
            }
            die;
        }
    }

    public function _validate_lightbox()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $wireTransferOut       = $this->prepareDisplayWireTransferOut();
        $this->displayWarning  = false;
        if ($wireTransferOut->getBankAccount()->getIdClient() !== $wireTransferOut->getClient()) {
            $this->displayWarning = false === $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')->isBankAccountValidatedOnceTime($wireTransferOut);
        }

        if (false === empty($this->params[0]) && $this->request->isMethod('POST') && $wireTransferOut) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WireTransferOutManager $wireTransferOutManager */
            $wireTransferOutManager = $this->get('unilend.service.wire_transfer_out_manager');
            if (in_array($wireTransferOut->getStatus(), [Virements::STATUS_CLIENT_VALIDATED])) {
                $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
                $wireTransferOutManager->validateTransfer($wireTransferOut, $user);

                $_SESSION['freeow']['title']   = 'Transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds a été validé avec succès ';
            } else {
                $_SESSION['freeow']['title']   = 'Transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a été validé.';
            }

            header('Location: ' . $this->lurl . '/transferts/virement_emprunteur');
            die;
        }
    }

    private function prepareDisplayWireTransferOut()
    {
        $this->hideDecoration();

        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \NumberFormatTest currencyFormatter */
            $this->currencyFormatter = $this->get('currency_formatter');

            $this->wireTransferOut       = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')->find($this->params[0]);
            $this->bankAccountRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
            $this->companyRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        }

        return $this->wireTransferOut;
    }

    public function _virement_emprunteur()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \NumberFormatTest currencyFormatter */
        $this->currencyFormatter = $this->get('currency_formatter');

        $wireTransferOutRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements');
        $this->bankAccountRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
        $this->companyRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');


        $this->wireTransferOuts[Virements::STATUS_CLIENT_VALIDATED] = $wireTransferOutRepository->findBy(['type' => Virements::TYPE_BORROWER, 'status' => Virements::STATUS_CLIENT_VALIDATED]);
        $this->wireTransferOuts[Virements::STATUS_PENDING]          = $wireTransferOutRepository->findBy(['type' => Virements::TYPE_BORROWER, 'status' => Virements::STATUS_PENDING]);
        $this->wireTransferOuts[Virements::STATUS_VALIDATED]        = $wireTransferOutRepository->findBy(['type' => Virements::TYPE_BORROWER, 'status' => Virements::STATUS_VALIDATED]);
    }
}
