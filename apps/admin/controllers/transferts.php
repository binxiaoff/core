<?php

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ {
    AttachmentType, Bids, Factures, LenderStatisticQueue, Notifications, OperationSubType, OperationType,
    Prelevements, ProjectRepaymentTask, ProjectsPouvoir, ProjectsStatus, Receptions, UniversignEntityInterface,
    Virements, Wallet, WalletType, Zones
};

class transfertsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);
        $this->menu_admin       = 'transferts';
        $this->statusOperations = [
            Receptions::STATUS_PENDING         => 'En attente',
            Receptions::STATUS_ASSIGNED_MANUAL => 'Manu',
            Receptions::STATUS_ASSIGNED_AUTO   => 'Auto',
            Receptions::STATUS_IGNORED_MANUAL  => 'Ignoré manu',
            Receptions::STATUS_IGNORED_AUTO    => 'Ignoré auto'
        ];

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
        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->receptions = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Receptions')->getLenderAttributions();
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _emprunteurs()
    {
        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->receptions = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Receptions')->getBorrowerAttributions();
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _attribues()
    {
        if (isset($this->params[0])) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var NumberFormatter $currencyFormatter */
            $currencyFormatter = $this->get('currency_formatter');

            $receptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions');

            $query    = $this->handleDataTablesRequest($this->request->query->all());
            $start    = $query['start'];
            $limit    = $query['length'];
            $draw     = $query['draw'];
            $search   = $query['search'];
            $sort     = $query['sort'];
            $dateFrom = null === $query['date_from'] ? $query['date_from'] : DateTime::createFromFormat('d/m/Y', $query['date_from']);
            $dateTo   = null === $query['date_to'] ? $query['date_to'] : DateTime::createFromFormat('d/m/Y', $query['date_to']);

            $error                   = '';
            $receptionsCount         = 0;
            $receptionsCountFiltered = 0;
            $affectedReceptions      = [];

            try {
                if ($this->params[0] === 'preteur') {
                    $receptionsCount         = $receptionRepository->getLenderAttributionsCount();
                    $receptionsCountFiltered = $receptionRepository->getLenderAttributionsCount($search, $dateFrom, $dateTo);
                    $receptions              = $receptionRepository->getLenderAttributions($limit, $start, $sort, $search, $dateFrom, $dateTo);
                } else {
                    $receptionsCount         = $receptionRepository->getBorrowerAttributionsCount();
                    $receptionsCountFiltered = $receptionRepository->getBorrowerAttributionsCount($search, $dateFrom, $dateTo);
                    $receptions              = $receptionRepository->getBorrowerAttributions($limit, $start, $sort, $search, $dateFrom, $dateTo);
                }

                foreach ($receptions as $reception) {
                    if (Receptions::STATUS_ASSIGNED_MANUAL == $reception->getStatusBo() && null !== $reception->getIdUser()) {
                        $attribution = $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() . '<br>' . $reception->getAssignmentDate()->format('d/m/Y H:i:s');
                    } else {
                        $attribution = $this->statusOperations[$reception->getStatusBo()];
                    }

                    $affectedReceptions[] = [
                        0  => $reception->getIdReception(),
                        1  => $reception->getMotif(),
                        2  => $currencyFormatter->formatCurrency(round(bcdiv($reception->getMontant(), 100, 4), 2), 'EUR'),
                        3  => $attribution,
                        4  => $reception->getIdClient() ? $reception->getIdClient()->getIdClient() : '',
                        5  => $reception->getIdproject() ? $reception->getIdproject()->getIdproject() : '',
                        6  => $reception->getAdded()->format('d/m/Y'),
                        7  => '',
                        8  => $reception->getComment(),
                        9  => $reception->getLigne(),
                        10 => Receptions::DIRECT_DEBIT_STATUS_REJECTED === $reception->getStatusPrelevement() || Receptions::WIRE_TRANSFER_STATUS_REJECTED === $reception->getStatusVirement()
                    ];
                }
            } catch (Exception $exception) {
                $error = 'une erreur est survenue lors de la récupération des réceptions attribuées.';
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning($error, ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
            }

            if (empty($error)) {
                $result = [
                    'draw'            => $draw,
                    'recordsTotal'    => $receptionsCount,
                    'recordsFiltered' => $receptionsCountFiltered,
                    'data'            => $affectedReceptions
                ];
            } else {
                $result = [
                    'draw'            => $draw,
                    'recordsTotal'    => $receptionsCount,
                    'recordsFiltered' => $receptionsCount,
                    'data'            => $receptionsCountFiltered,
                    'error'           => $error,
                ];
            }
        } else {
            $result = [
                'draw'            => 0,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'type d\'attribution non défini',
            ];
        }

        echo json_encode($result);
        die;
    }

    private function handleDataTablesRequest($query)
    {
        $search = [];
        if ('' !== $query['search']['value']) {
            foreach ($query['columns'] as $column) {
                if ('' !== $column['name'] && 'true' === $column['searchable']) {
                    $search[$column['name']] = $query['search']['value'];
                }
            }
        }

        $sort = [];
        foreach ($query['order'] as $order) {
            $columnName        = $query['columns'][$order['column']]['name'];
            $sort[$columnName] = $order['dir'];
        }

        return [
            'start'     => $query['start'],
            'length'    => $query['length'],
            'draw'      => $query['draw'],
            'search'    => $search,
            'sort'      => $sort,
            'date_from' => false === empty($query['date_from']) ? $query['date_from'] : null,
            'date_to'   => false === empty($query['date_to']) ? $query['date_to'] : null
        ];
    }

    public function _non_attribues()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($_POST['id_project'], $_POST['id_reception'])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
            $operationManager = $this->get('unilend.service.operation_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectPaymentManager $projectPaymentManager */
            $projectPaymentManager = $this->get('unilend.service_repayment.project_payment_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentTaskManager $projectRepaymentTaskManager */
            $projectRepaymentTaskManager = $this->get('unilend.service_repayment.project_repayment_task_manager');

            $project   = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']);
            $reception = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['id_reception']);
            $user      = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

            if (null !== $project && null !== $reception) {
                $entityManager->getConnection()->beginTransaction();
                try {
                    $reception
                        ->setIdProject($project)
                        ->setIdClient($project->getIdCompany()->getIdClientOwner())
                        ->setStatusBo(Receptions::STATUS_ASSIGNED_MANUAL)
                        ->setRemb(1)
                        ->setIdUser($user)
                        ->setAssignmentDate(new \DateTime());
                    $operationManager->provisionBorrowerWallet($reception);

                    if ($_POST['type_remb'] === 'remboursement_anticipe') {
                        $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_EARLY);
                        $projectRepaymentTaskManager->planEarlyRepaymentTask($project, $reception, $user);
                    } elseif ($_POST['type_remb'] === 'regularisation') {
                        $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_REGULARISATION);
                        if ($project->getStatus() === ProjectsStatus::REMBOURSEMENT) {
                            $projectPaymentManager->pay($reception, $user);
                        }
                    }
                    $entityManager->flush();
                    $entityManager->getConnection()->commit();
                } catch (Exception $exception) {
                    $this->get('logger')->error('Cannot affect the amount to a borrower. Error : ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
                    $entityManager->getConnection()->rollBack();
                }
            }

            header('Location: ' . $this->lurl . '/transferts/emprunteurs');
            die;
        }
    }

    public function _non_attribues_liste()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var NumberFormatter $currencyFormatter */
        $currencyFormatter = $this->get('currency_formatter');

        $receptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions');

        $query  = $this->handleDataTablesRequest($this->request->query->all());
        $start  = $query['start'];
        $limit  = $query['length'];
        $draw   = $query['draw'];
        $search = $query['search'];
        $sort   = $query['sort'];

        $error                   = '';
        $receptionsCount         = 0;
        $receptionsCountFiltered = 0;
        $affectedReceptions      = [];

        try {
            $receptionsCount         = $receptionRepository->getNonAttributionsCount();
            $receptionsCountFiltered = $receptionRepository->getNonAttributionsCount($search);
            $receptions              = $receptionRepository->getNonAttributions($limit, $start, $sort, $search);

            foreach ($receptions as $reception) {
                $affectedReceptions[] = [
                    $reception->getIdReception(),
                    $reception->getMotif(),
                    $currencyFormatter->formatCurrency(round(bcdiv($reception->getMontant(), 100, 4), 2), 'EUR'),
                    $reception->getAdded()->format('d/m/Y'),
                    substr($reception->getLigne(), 32, 2) . ' / ' . substr($reception->getLigne(), 7, 4),
                    '',
                    $reception->getLigne(),
                    $reception->getComment(),
                    Receptions::DIRECT_DEBIT_STATUS_REJECTED === $reception->getStatusPrelevement() || Receptions::WIRE_TRANSFER_STATUS_REJECTED === $reception->getStatusVirement()
                ];
            }
        } catch (Exception $exception) {
            $error = 'une erreur est survenue lors de la récupération des réceptions attribuées.';
            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->warning($error, ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }

        if (empty($error)) {
            $result = [
                'draw'            => $draw,
                'recordsTotal'    => $receptionsCount,
                'recordsFiltered' => $receptionsCountFiltered,
                'data'            => $affectedReceptions
            ];
        } else {
            $result = [
                'draw'            => $draw,
                'recordsTotal'    => $receptionsCount,
                'recordsFiltered' => $receptionsCountFiltered,
                'data'            => $affectedReceptions,
                'error'           => $error,
            ];
        }

        echo json_encode($result);
        die;
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
        $this->lPreteurs = [];

        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        if (isset($_POST['id'], $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['raison_sociale'], $_POST['id_reception'])) {
            $_SESSION['controlDoubleAttr'] = md5($_SESSION['user']['id_user']);

            if (empty($_POST['id']) && empty($_POST['nom']) && empty($_POST['email']) && empty($_POST['prenom']) && empty($_POST['raison_sociale'])) {
                $_SESSION['search_lender_attribution_error'][] = 'Veuillez remplir au moins un champ';
            }

            $email = empty($_POST['email']) ? null : trim(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL));
            if (false === $email) {
                $_SESSION['search_lender_attribution_error'][] = 'Format de l\'email est non valide';
            }

            $clientId = empty($_POST['id']) ? null : trim(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
            if (false === $clientId) {
                $_SESSION['search_lender_attribution_error'][] = 'L\'id du client doit être numérique';
            }

            $lastName = empty($_POST['nom']) ? null : trim(filter_var($_POST['nom'], FILTER_SANITIZE_STRING));
            if (false === $lastName) {
                $_SESSION['search_lender_attribution_error'][] = 'Le format du nom n\'est pas valide';
            }

            $firstName = empty($_POST['prenom']) ? null : trim(filter_var($_POST['prenom'], FILTER_SANITIZE_STRING));
            if (false === $firstName) {
                $_SESSION['search_lender_attribution_error'][] = 'Le format du prenom n\'est pas valide';
            }

            $companyName = empty($_POST['raison_sociale']) ? null : trim(filter_var($_POST['raison_sociale'], FILTER_SANITIZE_STRING));
            if (false === $companyName) {
                $_SESSION['search_lender_attribution_error'][] = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['search_lender_attribution_error'])) {
                header('Location:' . $this->lurl . '/transferts/attribution_preteur');
                die;
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientRepository */
            $clientRepository   = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
            $this->lPreteurs    = $clientRepository->findLenders($clientId, $email, $lastName, $firstName, $companyName);
            $this->id_reception = $_POST['id_reception'];
        }
    }

    public function _recherche_projet()
    {
        $this->hideDecoration();
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $project     = null;
        $receptionId = null;
        $statusLabel = '';

        $project     = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->request->query->getInt('id_project'));
        $receptionId = $this->request->query->getInt('id_reception');

        if (null !== $project) {
            $status      = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => $project->getStatus()]);
            $statusLabel = $status->getLabel();
        }

        $this->render(null, ['project' => $project, 'receptionId' => $receptionId, 'statusLabel' => $statusLabel, 'repaymentType' => $this->request->query->get('type_remb')]);
    }

    public function _attribuer_preteur()
    {
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
                $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

                $reception
                    ->setIdClient($wallet->getIdClient())
                    ->setStatusBo(Receptions::STATUS_ASSIGNED_MANUAL)
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

                        $keywords = [
                            'firstName'        => $preteurs->prenom,
                            'depositAmount'    => $this->ficelle->formatNumber($reception->getMontant() / 100),
                            'availableBalance' => $this->ficelle->formatNumber($wallet->getAvailableBalance()),
                            'lenderPattern'    => $wallet->getWireTransferPattern(),
                            'projectsLink'     => $this->furl . '/projets-a-financer',
                        ];

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation-manu', $keywords);

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

    public function _ignore()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if (empty($_POST['reception']) || false === filter_var($_POST['reception'], FILTER_VALIDATE_INT)) {
            echo 'ID opération manquant';
            return;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $reception     = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($_POST['reception']);

        if (null === $reception) {
            echo 'Opération inconnue';
            return;
        }

        $reception
            ->setStatusBo(Receptions::STATUS_IGNORED_MANUAL)
            ->setIdUser($entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']))
            ->setComment($_POST['comment']);

        $entityManager->flush();

        echo 'ok';
    }

    public function _comment()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if ($receptionId = $this->request->request->getInt('reception')) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $reception     = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($receptionId);
            if ($reception) {
                $reception->setComment($this->request->request->get('comment'));
                $entityManager->flush();
                echo json_encode(['error' => [], 'success' => true, 'data' => ['comment' => $this->request->request->get('comment')]]);
                return;
            }
        }
        echo json_encode(['error' => ['id reception n\'existe pas'], 'success' => false]);
    }

    public function _deblocage()
    {
        ini_set('memory_limit', '512M');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager $beneficialOwnerManager */
        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');

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
            $mandate                    = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findOneBy([
                'idProject' => $_POST['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);
            $proxy                      = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->findOneBy([
                'idProject' => $_POST['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);
            $beneficialOwnerDeclaration = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findOneBy([
                'idProject' => $_POST['id_project'],
                'status'    => UniversignEntityInterface::STATUS_SIGNED
            ], ['added' => 'DESC']);

            if (null === $mandate || null === $proxy || ($beneficialOwnerManager->projectNeedsBeneficialOwnerDeclaration($project) && null === $beneficialOwnerDeclaration)) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Mandat, pouvoir ou déclaration des bénéficiaires effectifs non signé pour le projet ' . $_POST['id_project'];
                header('Location: ' . $this->lurl . '/transferts/deblocage/');
                die;
            }

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');

            $ongoingRepaymentTask = $entityManager
                ->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
                ->findOneBy(['status' => ProjectRepaymentTask::STATUS_IN_PROGRESS]);
            if (null !== $ongoingRepaymentTask) {
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
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\NotificationManager $notificationManager */
            $notificationManager = $this->get('unilend.service.notification_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
            $operationManager = $this->get('unilend.service.operation_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SlackManager $slackManager */
            $slackManager = $this->container->get('unilend.service.slack_manager');
            /** @var \accepted_bids $acceptedBids */
            $acceptedBids = $this->loadData('accepted_bids');

            try {
                $loanRepository             = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
                $operationRepository        = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
                $operationTypeRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType');
                $operationSubTypeRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType');

                $loanOperationType = $operationTypeRepository->findOneBy(['label' => OperationType::LENDER_LOAN]);

                $offset = 0;
                $limit  = 50;
                while ($allLoans = $loanRepository->findBy(['idProject' => $project], ['idLoan' => 'ASC'], $limit, $offset)) {
                    foreach ($allLoans as $loan) {
                        $loanOperation = $operationRepository->findOneBy(['idLoan' => $loan, 'idType' => $loanOperationType]);
                        if (null === $loanOperation) {
                            $operationManager->loan($loan);
                        }
                    }
                    unset($allLoans);
                    $offset += $limit;
                }

                $commissionFundsOperationType = $operationSubTypeRepository->findOneBy(['label' => OperationSubType::BORROWER_COMMISSION_FUNDS]);
                $commissionFundsOperation     = $operationRepository->findOneBy(['idProject' => $project, 'idSubType' => $commissionFundsOperationType]);
                if (null === $commissionFundsOperation) {
                    $commission = $projectManager->getCommissionFunds($project, true);
                    $operationManager->projectCommission($project, $commission);
                }

                $fundsInvoice = $entityManager->getRepository('UnilendCoreBusinessBundle:Factures')->findOneBy(['idProject' => $project, 'typeCommission' => Factures::TYPE_COMMISSION_FUNDS]);
                if (null === $fundsInvoice) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\InvoiceManager $invoiceManager */
                    $invoiceManager = $this->get('unilend.service.invoice_manager');
                    $invoiceManager->createFundsInvoice($project);
                }

                $directDebits = $entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements')->findOneBy(['idProject' => $project]);
                if (null === $directDebits) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerManager $borrowerManager */
                    $borrowerManager   = $this->get('unilend.service.borrower_manager');
                    $bankTransferLabel = $borrowerManager->getBorrowerBankTransferLabel($project);

                    $paymentSchedules = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findBy(['idProject' => $project]);

                    foreach ($paymentSchedules as $paymentSchedule) {
                        $directDebitDate = (clone $paymentSchedule->getDateEcheanceEmprunteur())->modify('15 days ago');

                        $directDebit = new Prelevements();
                        $directDebit
                            ->setIdClient($project->getIdCompany()->getIdClientOwner())
                            ->setIdProject($project)
                            ->setMotif($bankTransferLabel)
                            ->setMontant($paymentSchedule->getCapital() + $paymentSchedule->getInterets() + $paymentSchedule->getCommission() + $paymentSchedule->getTva())
                            ->setBic(str_replace(' ', '', $mandate->getBic()))
                            ->setIban(str_replace(' ', '', $mandate->getIban()))
                            ->setTypePrelevement(Prelevements::TYPE_RECURRENT)
                            ->setType(Prelevements::CLIENT_TYPE_BORROWER)
                            ->setNumPrelevement($paymentSchedule->getOrdre())
                            ->setDateExecutionDemandePrelevement($directDebitDate)
                            ->setDateEcheanceEmprunteur($paymentSchedule->getDateEcheanceEmprunteur())
                            ->setStatus(Prelevements::STATUS_PENDING);

                        $entityManager->persist($directDebit);

                    }
                    $entityManager->flush();
                }

                $proxy->setStatusRemb(ProjectsPouvoir::STATUS_REPAYMENT_VALIDATED);
                $entityManager->flush($proxy);

                $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::REMBOURSEMENT, $project);

                $allAcceptedBids = $acceptedBids->getDistinctBids($project->getIdProject());
                $lastLoans       = array();

                foreach ($allAcceptedBids as $bid) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Bids $bidEntity */
                    $bidEntity    = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->find($bid['id_bid']);
                    $bidAmount    = round(bcdiv($bid['amount'], 100, 4), 2);
                    $notification = $notificationManager->createNotification(Notifications::TYPE_LOAN_ACCEPTED, $bidEntity->getIdLenderAccount()->getIdClient()->getIdClient(),
                        $project->getIdProject(), $bidAmount, $bid['id_bid']);

                    $loansForBid = $acceptedBids->select('id_bid = ' . $bid['id_bid']);

                    foreach ($loansForBid as $loan) {
                        if (in_array($loan['id_loan'], $lastLoans) === false) {
                            $notificationManager->createEmailNotification($notification->id_notification, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
                                $bidEntity->getIdLenderAccount()->getIdClient()->getIdClient(), null, null,
                                $loan['id_loan']);
                            $lastLoans[] = $loan['id_loan'];
                        }
                    }
                }

                $mailerManager->sendLoanAccepted($project);
                $mailerManager->sendBorrowerBill($project);

                $_SESSION['freeow']['title']   = 'Déblocage des fonds';
                $_SESSION['freeow']['message'] = 'Le déblocage a été fait avec succès';

                if ($this->getParameter('kernel.environment') === 'prod') {
                    try {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Ekomi $ekomi */
                        $ekomi = $this->get('unilend.service.ekomi');
                        $ekomi->sendProjectEmail($project);
                    } catch (\Exception $exception) {
                        $logger->error('Ekomi send project email failed. Error message : ' . $exception->getMessage());
                    }
                }

                $slackMessage = $slackManager->getProjectName($project) . ' - Fonds débloqués par ' . $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'];
            } catch (\Exception $exception) {
                $logger->error('Release funds failed for project : ' . $project->getIdProject() . ', but the process is recoverable, please try it again later. Error : ' . $exception->getMessage());

                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Une erreur s\'est produit. Les fonds ne sont pas débloqués';

                $slackMessage = $slackManager->getProjectName($project) . ' - :warning: Une erreur est survenue lors du déblocage des fonds par  ' . $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'];
            }

            try {
                $slackManager->sendMessage($slackMessage);
            } catch (\Exception $exception) {
                $logger->error('Slack message for release funds failed. Error message : ' . $exception->getMessage());
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

            $this->aProjects[$index]['needsBeneficialOwnerDeclaration'] = $beneficialOwnerManager->projectNeedsBeneficialOwnerDeclaration($project);
            if ($this->aProjects[$index]['needsBeneficialOwnerDeclaration']) {
                $beneficialOwnerDeclaration = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findOneBy([
                    'idProject' => $aProject['id_project'],
                    'status'    => UniversignEntityInterface::STATUS_SIGNED
                ], ['added' => 'DESC']);

                if (null !== $beneficialOwnerDeclaration) {
                    $this->aProjects[$index]['beneficial_owner_declaration']        = $beneficialOwnerDeclaration->getName();
                    $this->aProjects[$index]['beneficial_owner_declaration_status'] = $beneficialOwnerDeclaration->getStatus();
                }
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
            if ($bids->exist($originalWallet->getId(), 'status = ' . Bids::STATUS_PENDING . ' AND id_lender_account ')) {
                $this->addErrorMessageAndRedirect('Le défunt a des bids en cours.');
            }

            /** @var \loans $loans */
            $loans                 = $this->loadData('loans');
            $loansInRepayment      = $loans->getLoansForProjectsWithStatus($originalWallet->getId(), [ProjectsStatus::FUNDE, ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME]);
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
     * @param \loans $loans
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

        $this->displayWarning = false;
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
