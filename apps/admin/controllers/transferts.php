<?php

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatisticQueue;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class transfertsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll   = true;
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
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->receptions = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Receptions')->getLenderAttributions();
        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _emprunteurs()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->receptions = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Receptions')->getBorrowerAttributions();
        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _non_attribues()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->nonAttributedReceptions = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findNonAttributed();

        if (isset($_POST['id_project'], $_POST['id_reception'])) {
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
                } elseif ($_POST['type_remb'] === 'regularisation') {
                    $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_REGULARISATION);
                    $this->updateEcheances($project->getIdProject(), $reception->getMontant());
                }

                $entityManager->flush();
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
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->hideDecoration();

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');
    }

    public function _attribution_preteur()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->hideDecoration();
        $this->lPreteurs = [];


        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        if (isset($_POST['id'], $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['raison_sociale'], $_POST['id_reception'])) {
            $_SESSION['controlDoubleAttr'] = md5($_SESSION['user']['id_user']);

            if (empty($_POST['id']) && empty($_POST['nom']) && empty($_POST['email']) && empty($_POST['prenom']) && empty($_POST['raison_sociale'])) {
                $_SESSION['search_lender_attribution_error'][] = 'Veuillez remplir au moins un champ';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['search_lender_attribution_error'][] = 'Format de l\'email est non valide';
            }

            $clientId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $clientId) {
                $_SESSION['search_lender_attribution_error'][] = 'L\'id du client doit être numérique';
            }

            $lastName = empty($_POST['nom']) ? '' : filter_var($_POST['nom'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['search_lender_attribution_error'][] = 'Le format du nom n\'est pas valide';
            }

            $firstName = empty($_POST['prenom']) ? '' : filter_var($_POST['prenom'], FILTER_SANITIZE_STRING);
            if (false === $firstName) {
                $_SESSION['search_lender_attribution_error'][] = 'Le format du prenom n\'est pas valide';
            }

            $companyName = empty($_POST['raison_sociale']) ? '' : filter_var($_POST['raison_sociale'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['search_lender_attribution_error'][] = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['search_lender_attribution_error'])) {
                header('Location:' . $this->lurl . '/transferts/attribution_preteur');
                die;
            }

            $this->lPreteurs    = $this->clients->searchPreteurs(trim($clientId), trim($lastName), trim($email), trim($firstName), trim($companyName));
            $this->id_reception = $_POST['id_reception'];
        }
    }

    public function _attribuer_preteur()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \clients $preteurs */
        $preteurs = $this->loadData('clients');
        /** @var \notifications notifications */
        $this->notifications = $this->loadData('notifications');
        /** @var \clients_gestion_notifications clients_gestion_notifications */
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif clients_gestion_mails_notif */
        $this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
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
                    $this->notifications->type      = Notifications::TYPE_BANK_TRANSFER_CREDIT;
                    $this->notifications->id_lender = $wallet->getId();
                    $this->notifications->amount    = $reception->getMontant();
                    $this->notifications->create();

                    $provisionOperation   = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWireTransferIn' => $reception]);
                    $walletBalanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy([
                        'idOperation' => $provisionOperation,
                        'idWallet'    => $wallet
                    ]);

                    $this->clients_gestion_mails_notif->id_client                 = $wallet->getIdClient()->getIdClient();
                    $this->clients_gestion_mails_notif->id_notif                  = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
                    $this->clients_gestion_mails_notif->date_notif                = date('Y-m-d H:i:s');
                    $this->clients_gestion_mails_notif->id_notification           = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->id_wallet_balance_history = $walletBalanceHistory->getId();
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
                        try {
                            $message->setTo($preteurs->email);
                            $mailer = $this->get('mailer');
                            $mailer->send($message);
                        } catch (\Exception $exception) {
                            $this->get('logger')->warning(
                                'Could not send email : preteur-alimentation-manu - Exception: ' . $exception->getMessage(),
                                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $preteurs->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                            );
                        }
                    }

                    echo $reception->getIdClient()->getIdClient();
                }
            }
        }
    }

    public function _annuler_attribution_preteur()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

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
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->hideDecoration();
        $this->autoFireView = false;

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

                    $operationType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType::BORROWER_PROVISION]);
                    $operation     = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([
                        'idWireTransferIn' => $reception->getIdReception(),
                        'idWalletCreditor' => $wallet,
                        'idType'           => $operationType
                    ]);
                    if (null === $operation) {
                        return false;
                    }

                    $operationManager->cancelProvisionBorrowerWallet($wallet, $amount, $reception);

                    $reception->setIdClient(null)
                              ->setIdProject(null)
                              ->setStatusBo(Receptions::STATUS_PENDING)
                              ->setRemb(0); // todo: delete the field
                    $entityManager->flush();

                    $eche   = $echeanciers_emprunteur->select('id_project = ' . $projectId . ' AND status_emprunteur = 1', 'ordre DESC');
                    $newsum = round(bcdiv($reception->getMontant(), 100, 4), 2);

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
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->hideDecoration();
        $this->autoFireView = false;

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

                    $operationType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType::BORROWER_PROVISION]);
                    $operation     = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([
                        'idWireTransferIn' => $reception->getIdReception(),
                        'idWalletCreditor' => $wallet,
                        'idType'           => $operationType
                    ]);
                    if (null === $operation) {
                        return false;
                    }

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
                    $operationManager = $this->get('unilend.service.operation_manager');
                    $operationManager->cancelProvisionBorrowerWallet($wallet, $amount, $reception);

                    $reception->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED);
                    $reception->setRemb(0);
                    $entityManager->flush();

                    $eche   = $echeanciers_emprunteur->select('id_project = ' . $reception->getIdProject()->getIdProject() . ' AND status_emprunteur = 1', 'ordre DESC');
                    $newsum = round(bcdiv($reception->getMontant(), 100, 4), 2);

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
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->menu_admin = isset($this->lZonesHeader) && in_array(Zones::ZONE_LABEL_TRANSFERS, $this->lZonesHeader) ? 'transferts' : 'preteurs';

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
            if ($this->clients->get($this->params[0])) {
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
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->menu_admin = isset($this->lZonesHeader) && in_array(Zones::ZONE_LABEL_TRANSFERS, $this->lZonesHeader) ? 'transferts' : 'preteurs';

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
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->menu_admin = isset($this->lZonesHeader) && in_array(Zones::ZONE_LABEL_TRANSFERS, $this->lZonesHeader) ? 'transferts' : 'preteurs';

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
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

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
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Bids $bidEntity */
                    $bidEntity    = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->find($bid['id_bid']);
                    $bidAmount    = round(bcdiv($bid['amount'], 100, 4), 2);
                    $notification = $notificationManager->createNotification(Notifications::TYPE_LOAN_ACCEPTED, $bidEntity->getIdLenderAccount()->getIdClient()->getIdClient(), $project->getIdProject(), $bidAmount, $bid['id_bid']);

                    $loansForBid = $acceptedBids->select('id_bid = ' . $bid['id_bid']);

                    foreach ($loansForBid as $loan) {
                        if (in_array($loan['id_loan'], $lastLoans) === false) {
                            $notificationManager->createEmailNotification($notification->id_notification, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, $bidEntity->getIdLenderAccount()->getIdClient()->getIdClient(), null, null,
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

                if ($this->getParameter('kernel.environment') === 'prod') {
                    try {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Ekomi $ekomi */
                        $ekomi = $this->get('unilend.service.ekomi');
                        $ekomi->sendProjectEmail($project);
                    } catch (\Exception $exception) {
                        // Nothing to do, but it must not disturb the DB transaction.
                        $logger->error('Ekomi send project email failed. Error message : ' . $exception->getMessage());
                    }
                }

                try {
                    $slackManager = $this->container->get('unilend.service.slack_manager');
                    $message      = $slackManager->getProjectName($project) . ' - Fonds débloqués par ' . $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'];
                    $slackManager->sendMessage($message);
                } catch (\Exception $exception) {
                    // Nothing to do, but it must not disturb the DB transaction.
                    $logger->error('Slack message for release funds failed. Error message : ' . $exception->getMessage());
                }

            } catch (\Exception $exception) {
                $entityManager->getConnection()->rollBack();
                $logger->error('Release funds failed for project : ' . $project->getIdProject() . '. The process has been rollbacked. Error : ' . $exception->getMessage());

                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Une erreur s\'est produit. Les fonds ne sont pas débloqués';
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
            $project                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($aProject['id_project']);
            $mandate                 = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findOneBy([
                'idProject' => $aProject['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);
            $proxy                   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->findOneBy([
                'idProject' => $aProject['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);

            if ($mandate) {
                $this->aProjects[$index]['mandat']        = $mandate->getName();
                $this->aProjects[$index]['status_mandat'] = $mandate->getStatus();
            }

            if ($proxy) {
                $this->aProjects[$index]['url_pdf']          = $proxy->getName();
                $this->aProjects[$index]['status_remb']      = $proxy->getStatusRemb();
                $this->aProjects[$index]['authority_status'] = $proxy->getStatus();
            }

            $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($project->getIdCompany()->getIdClientOwner());

            $this->aProjects[$index]['bic']  = '';
            $this->aProjects[$index]['iban'] = '';
            if ($bankAccount) {
                $this->aProjects[$index]['bic']  = $bankAccount->getBic();
                $this->aProjects[$index]['iban'] = $bankAccount->getIban();
                $bankAccountAttachment           = $bankAccount->getAttachment();
            }

            $this->aProjects[$index]['rib']    = '';
            $this->aProjects[$index]['id_rib'] = '';

            if (false === empty($bankAccountAttachment)) {
                $this->aProjects[$index]['rib']    = $bankAccountAttachment->getPath();
                $this->aProjects[$index]['id_rib'] = $bankAccountAttachment->getId();
            }

            $kbis = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->getAttachedAttachments($aProject['id_project'], AttachmentType::KBIS);

            $this->aProjects[$index]['kbis']    = '';
            $this->aProjects[$index]['id_kbis'] = '';

            if (false === empty($kbis[0])) {
                $attachment                         = $kbis[0]->getAttachment();
                $this->aProjects[$index]['kbis']    = $attachment->getPath();
                $this->aProjects[$index]['id_kbis'] = $attachment->getId();
            }
        }
    }

    public function _succession()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

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
            $walletRepository       = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
            $clientStatusRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus');

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
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus $lastStatusEntity */
            $lastStatusEntity = $clientStatusRepository->getLastClientStatus($newOwner->id_client);
            $lastStatus       = (null === $lastStatusEntity) ? null : $lastStatusEntity->getStatus();

            if ($lastStatus != \Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus::VALIDATED) {
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

                    $clientStatusManager->addClientStatus(
                        $newOwner,
                        $_SESSION['user']['id_user'],
                        $lastStatus,
                        'Reçu solde (' . $this->ficelle->formatNumber($originalClientBalance) . ') et prêts (' . $numberLoans . ') du compte ' . $originalClient->id_client
                    );

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

    public function _validate_lightbox()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

        $this->hideDecoration();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($this->params[0])) {
            /** @var \NumberFormatTest currencyFormatter */
            $this->currencyFormatter = $this->get('currency_formatter');

            $this->wireTransferOut       = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')->find($this->params[0]);
            $this->bankAccountRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
            $this->companyRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        }

        $this->displayWarning  = false;
        if ($this->wireTransferOut->getBankAccount()->getIdClient() !== $this->wireTransferOut->getClient()) {
            $this->displayWarning = false === $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')->isBankAccountValidatedOnceTime($this->wireTransferOut);
        }

        if (false === empty($this->params[0]) && $this->request->isMethod('POST') && $this->wireTransferOut) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WireTransferOutManager $wireTransferOutManager */
            $wireTransferOutManager = $this->get('unilend.service.wire_transfer_out_manager');
            if (in_array($this->wireTransferOut->getStatus(), [Virements::STATUS_CLIENT_VALIDATED])) {
                $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
                $wireTransferOutManager->validateTransfer($this->wireTransferOut, $user);

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

    public function _virement_emprunteur()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);

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
