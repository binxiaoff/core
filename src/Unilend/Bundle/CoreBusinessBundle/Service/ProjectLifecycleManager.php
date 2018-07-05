<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\{
    EntityManager, EntityRepository, NonUniqueResultException, OptimisticLockException
};
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AcceptedBids, Autobid, Bids, Clients, ClientsGestionTypeNotif, ClientsStatus, Notifications, Projects, ProjectsStatus, TaxType, UnderlyingContract, UnderlyingContractAttributeType, Users, Wallet,
    WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\core\Loader;

class ProjectLifecycleManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var BidManager */
    private $bidManager;
    /** @var LoanManager */
    private $loanManager;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectRateSettingsManager */
    private $projectRateSettingsManager;
    /** @var ProductManager */
    private $productManager;
    /** @var ContractAttributeManager */
    private $contractAttributeManager;
    /** @var \dates */
    private $datesManager;
    /** @var \jours_ouvres */
    private $workingDay;
    /** @var LoggerInterface */
    private $logger;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var AutoBidSettingsManager */
    private $autobidSettingsManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var RouterInterface */
    private $router;
    /** @var string */
    private $frontUrl;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var \NumberFormatter */
    private $currencyFormatter;
    /** @var CIPManager */
    private $cipManager;

    /**
     * @param EntityManagerSimulator     $entityManagerSimulator
     * @param EntityManager              $entityManager
     * @param BidManager                 $bidManager
     * @param LoanManager                $loanManager
     * @param NotificationManager        $notificationManager
     * @param MailerManager              $mailerManager
     * @param ProjectRateSettingsManager $projectRateSettingsManager
     * @param ProductManager             $productManager
     * @param ContractAttributeManager   $contractAttributeManager
     * @param ProjectStatusManager       $projectStatusManager
     * @param ProjectManager             $projectManager
     * @param AutoBidSettingsManager     $autobidSettingsManager
     * @param TranslatorInterface        $translator
     * @param TemplateMessageProvider    $messageProvider
     * @param \Swift_Mailer              $mailer
     * @param RouterInterface            $router
     * @param string                     $frontUrl
     * @param \NumberFormatter           $numberFormatter
     * @param \NumberFormatter           $currencyFormatter
     * @param CIPManager                 $cipManager
     *
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        BidManager $bidManager,
        LoanManager $loanManager,
        NotificationManager $notificationManager,
        MailerManager $mailerManager,
        ProjectRateSettingsManager $projectRateSettingsManager,
        ProductManager $productManager,
        ContractAttributeManager $contractAttributeManager,
        ProjectStatusManager $projectStatusManager,
        ProjectManager $projectManager,
        AutoBidSettingsManager $autobidSettingsManager,
        TranslatorInterface $translator,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        RouterInterface $router,
        string $frontUrl,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter,
        CIPManager $cipManager
    )
    {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->bidManager                 = $bidManager;
        $this->loanManager                = $loanManager;
        $this->notificationManager        = $notificationManager;
        $this->mailerManager              = $mailerManager;
        $this->projectRateSettingsManager = $projectRateSettingsManager;
        $this->productManager             = $productManager;
        $this->contractAttributeManager   = $contractAttributeManager;
        $this->projectStatusManager       = $projectStatusManager;
        $this->projectManager             = $projectManager;
        $this->autobidSettingsManager     = $autobidSettingsManager;
        $this->translator                 = $translator;
        $this->messageProvider            = $messageProvider;
        $this->mailer                     = $mailer;
        $this->router                     = $router;
        $this->frontUrl                   = $frontUrl;
        $this->numberFormatter            = $numberFormatter;
        $this->currencyFormatter          = $currencyFormatter;
        $this->cipManager                 = $cipManager;

        $this->datesManager = Loader::loadLib('dates');
        $this->workingDay   = Loader::loadLib('jours_ouvres');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function prePublish(\projects $project): void
    {
        $this->autoBid($project);
        $isFunded = $this->projectManager->isFunded($project);

        if ($isFunded) {
            $this->markAsFunded($project);
        }
        $this->reBidAutoBidDeeply($project, BidManager::MODE_REBID_AUTO_BID_CREATE, false);

        $currentDate      = new \DateTime();
        $projectEndDate   = $this->projectManager->getProjectEndDate($project);
        $isRateMinReached = $this->projectManager->isRateMinReached($project);

        /**
         * We are trying to set the date_fin here to be sure that the call of sendBidRejected in sendAcceptedOrRejectedBidNotifications
         * when publishing the project will use the same date for all bid rejection emails.
         * In fact, it may happens that this function uses date_retrait on the begin, and date_fin once funding cron is launched
         */
        if ($projectEndDate <= $currentDate || true === $isRateMinReached) {
            $project->date_fin = $currentDate->format('Y-m-d H:i:s');
            $project->update();
        }
        /**
         * Change of behaviour: send it only if the rate min is not reached,
         * to avoid calling sendFundedAndFinishedToBorrower immediately after this one in funding cron
         */
        if (true === $isFunded && false === $isRateMinReached) {
            $this->mailerManager->sendFundedToBorrower($project);
        }

        $this->insertNewProjectEmails($project);
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::AUTO_BID_PLACED, $project);
    }

    /**
     * @param \projects $project
     */
    public function publish(\projects $project): void
    {
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::EN_FUNDING, $project);
        $this->insertNewProjectNotification($project);
        try {
            $this->sendAcceptedOrRejectedBidNotifications($project);
        } catch (OptimisticLockException $exception) {
            $this->logger->error(
                'Error while inserting new project notifications on the project: ' . $project->id_project . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'id_project' => $project->id_project, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @param \projects $project
     * @param bool      $sendNotification
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function checkBids(\projects $project, bool $sendNotification): void
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog       = $this->entityManagerSimulator->getRepository('bids_logs');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLogContext      = [];
        $bBidsLogs        = false;
        $iRejectedBids    = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $project->amount;
        $iBidTotal        = $legacyBid->getSoldeBid($project->id_project);

        $oBidLog->debut = date('Y-m-d H:i:s');

        if ($iBidTotal >= $iBorrowAmount) {
            $bids = $bidRepository->findBy(['idProject' => $project->id_project, 'status' => Bids::STATUS_PENDING], ['rate' => 'ASC', 'ordre' => 'ASC']);
            foreach ($bids as $bid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated = bcadd($iBidsAccumulated, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                } else {
                    $bBidsLogs = true;
                    if (null === $bid->getAutobid()) { // non-auto-bid
                        $this->bidManager->reject($bid, $sendNotification);
                    } else {
                        // For a autobid, we don't send reject notification, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $bid->setStatus(Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID);
                        $this->entityManager->flush($bid);
                    }

                    $iRejectedBids++;
                }
            }

            $aLogContext['Project ID']    = $project->id_project;
            $aLogContext['Balance']       = $iBidTotal;
            $aLogContext['Rejected bids'] = $iRejectedBids;
        }

        if ($bBidsLogs == true) {
            $oBidLog->id_project      = $project->id_project;
            $oBidLog->nb_bids_encours = $bidRepository->countBy(['idProject' => $project->id_project, 'status' => Bids::STATUS_PENDING]);
            $oBidLog->nb_bids_ko      = $iRejectedBids;
            $oBidLog->total_bids      = $bidRepository->countBy(['idProject' => $project->id_project]);
            $oBidLog->total_bids_ko   = $bidRepository->countBy(['idProject' => $project->id_project, 'status' => Bids::STATUS_REJECTED]);
            $oBidLog->rate_max        = $legacyBid->getProjectMaxRate($project);
            $oBidLog->fin             = date('Y-m-d H:i:s');
            $oBidLog->create();
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info(
                'Check bid info: ' . var_export($aLogContext, true) . ' (project ' . $project->id_project . ')',
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
            );
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function autoBid(\projects $project): void
    {
        if ($project->status == \projects_status::A_FUNDER) {
            $this->bidAllAutoBid($project);
        } elseif ($project->status == \projects_status::EN_FUNDING) {
            $this->reBidAutoBid($project, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        }
    }

    /**
     * @param \projects $project
     */
    private function bidAllAutoBid(\projects $project): void
    {
        $autoBidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        /** @var \project_period $projectPeriods */
        $projectPeriods = $this->entityManagerSimulator->getRepository('project_period');
        $projectEntity  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);

        if ($projectPeriods->getPeriod($project->period)) {
            $rateRange = $this->bidManager->getProjectRateRange($project);
            $iOffset   = 0;
            $iLimit    = 100;
            while ($autoBidSettings = $autoBidRepository->findBy(
                ['evaluation' => $project->risk, 'idPeriod' => $projectPeriods->id_period, 'status' => Autobid::STATUS_ACTIVE],
                ['idAutobid' => 'ASC'],
                $iLimit,
                $iOffset
            )) {
                $iOffset += $iLimit;
                foreach ($autoBidSettings as $autoBidSetting) {
                    try {
                        $this->bidManager->bidByAutoBidSettings($autoBidSetting, $projectEntity, $rateRange['rate_max']);
                    } catch (\Exception $exception) {
                        continue;
                    }
                }
            }

            /** @var \bids $oBid */
            $oBid = $this->entityManagerSimulator->getRepository('bids');
            $oBid->shuffleAutoBidOrder($project->id_project);
        }
    }

    /**
     * @param \projects $project
     * @param int       $mode
     * @param bool      $sendNotification
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function reBidAutoBid(\projects $project, int $mode, bool $sendNotification): void
    {
        /** @var \settings $oSettings */
        $oSettings = $this->entityManagerSimulator->getRepository('settings');
        /** @var \bids $legacyBid */
        $legacyBid     = $this->entityManagerSimulator->getRepository('bids');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $oSettings->get('Auto-bid step', 'type');
        $fStep       = (float) $oSettings->value;
        $currentRate = bcsub($legacyBid->getProjectMaxRate($project), $fStep, 1);
        while ($autoBids = $bidRepository->getAutoBids($project->id_project, Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID)) {
            foreach ($autoBids as $bid) {
                $this->bidManager->reBidAutoBidOrReject($bid, $currentRate, $mode, $sendNotification);
            }
        }
    }

    /**
     * @param \projects $project
     * @param int       $mode
     * @param bool      $sendNotification
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function reBidAutoBidDeeply(\projects $project, int $mode, bool $sendNotification): void
    {
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $this->checkBids($project, $sendNotification);
        $temporarilyRefusedAutoBid = $bidRepository->getAutoBids($project->id_project, Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID, 1);
        if (false === empty($temporarilyRefusedAutoBid)) {
            $this->reBidAutoBid($project, $mode, $sendNotification);
            $this->reBidAutoBidDeeply($project, $mode, $sendNotification);
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function buildLoans(\projects $project): void
    {
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::BID_TERMINATED, $project);
        $this->reBidAutoBidDeeply($project, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::FUNDE, $project);
        $this->acceptBids($project);

        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        $product->get($project->id_product);
        $contractTypes = array_column($this->productManager->getAvailableContracts($product), 'label');

        if (in_array(UnderlyingContract::CONTRACT_IFP, $contractTypes) && in_array(UnderlyingContract::CONTRACT_BDC, $contractTypes)) {
            $this->buildLoanIFPAndBDC($project);
        } elseif (in_array(UnderlyingContract::CONTRACT_IFP, $contractTypes) && in_array(UnderlyingContract::CONTRACT_MINIBON, $contractTypes)) {
            $projectEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
            $this->buildMinibonPrioritizedMixedLoanWithIfp($projectEntity);
        } elseif (in_array(UnderlyingContract::CONTRACT_IFP, $contractTypes)) {
            $this->buildLoanIfp($project);
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function acceptBids(\projects $project)
    {
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $criteria      = ['idProject' => $project->id_project, 'status' => Bids::STATUS_PENDING];
        $bids          = $bidRepository->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $bidBalance    = 0;

        foreach ($bids as $bid) {
            if ($bid) {
                if ($bidBalance < $project->amount) {
                    $bidAmount      = round(bcdiv($bid->getAmount(), 100, 4), 2);
                    $bidBalance     = round(bcadd($bidBalance, $bidAmount, 4), 2);
                    $acceptedAmount = null;

                    if ($bidBalance > $project->amount) {
                        $cutAmount      = round(bcsub($bidBalance, $project->amount, 4), 2);
                        $acceptedAmount = round(bcsub($bidAmount, $cutAmount, 4));
                    }
                    $this->bidManager->accept($bid, $acceptedAmount);

                } else {
                    $this->bidManager->reject($bid, true);
                }
            }
        }
    }

    /**
     * An alternative rule to build the loans that is IFP prioritized. Don't remove it, as it can be used one day. (Retired since 05/2018, ticket RUN-2991)
     *
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function buildLoanIfpAndMinibon(\projects $project): void
    {
        $this->buildIfpPrioritizedMixedLoan($project, UnderlyingContract::CONTRACT_MINIBON);
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function buildLoanIFPAndBDC(\projects $project): void
    {
        $this->buildIfpPrioritizedMixedLoan($project, UnderlyingContract::CONTRACT_BDC);
    }

    /**
     * @param \projects $project
     * @param string    $additionalContractLabel
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function buildIfpPrioritizedMixedLoan(\projects $project, string $additionalContractLabel): void
    {
        $ifpContract = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
        if (null === $ifpContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_IFP . ' does not exist.');
        }

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($ifpContract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        } else {
            $IfpLoanAmountMax = $contractAttrVars[0];
        }

        $additionalContract = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->findOneBy(['label' => $additionalContractLabel]);
        if (null === $additionalContract) {
            throw new \InvalidArgumentException('The contract ' . $additionalContractLabel . ' does not exist.');
        }

        $acceptedBidsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AcceptedBids');
        /** @var Wallet $wallet */
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findLendersWithAcceptedBidsByProject($project->id_project) as $wallet) {
            $acceptedBids   = $acceptedBidsRepository->findAcceptedBidsByLenderAndProject($wallet, $project->id_project);
            $loansLenderSum = 0;
            $isIfpContract  = true;
            $ifpBids        = [];

            /** @var AcceptedBids $acceptedBid */
            foreach ($acceptedBids as $acceptedBid) {
                if (false === $wallet->getIdClient()->isNaturalPerson()) {
                    $this->loanManager->create([$acceptedBid], $additionalContract);
                    continue;
                }

                $bidAmount = round(bcdiv($acceptedBid->getAmount(), 100, 4), 2);
                if (true === $isIfpContract && bccomp(bcadd($loansLenderSum, $bidAmount, 2), $IfpLoanAmountMax, 2) <= 0) {
                    $loansLenderSum += $bidAmount;
                    $ifpBids[]      = $acceptedBid;
                    continue;
                }

                if (false === $isIfpContract || bccomp($loansLenderSum, $IfpLoanAmountMax) == 0) {
                    $this->loanManager->create([$acceptedBid], $additionalContract);
                    continue;
                }

                // Greater than IFP max amount ? create additional contract loan, split it if needed. Duplicate accepted bid, as there are two loans for one accepted bid
                $isIfpContract = false;
                $notIfpAmount  = bcsub(bcadd($loansLenderSum, $bidAmount, 2), $IfpLoanAmountMax, 2);

                $clonedAcceptedBid = clone $acceptedBid;
                $clonedAcceptedBid->setAmount(bcmul($notIfpAmount, 100));
                $this->entityManager->persist($clonedAcceptedBid);
                $this->entityManager->flush($clonedAcceptedBid);

                $this->loanManager->create([$clonedAcceptedBid], $additionalContract);

                $remainingAmount = bcsub($bidAmount, $notIfpAmount, 2);
                if (0 < $remainingAmount) {
                    $acceptedBid->setAmount(bcmul($remainingAmount, 100));
                    $this->entityManager->flush($acceptedBid);
                    $ifpBids[] = $acceptedBid;
                }

                $loansLenderSum = $IfpLoanAmountMax;
            }

            if ($wallet->getIdClient()->isNaturalPerson()) {
                $this->loanManager->create($ifpBids, $ifpContract);
            }
        }
    }

    /**
     * @param \projects $project
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    private function buildLoanIfp(\projects $project): void
    {
        $ifpContract = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
        if (null === $ifpContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_IFP . ' does not exist.');
        }

        $acceptedBidsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AcceptedBids');
        $lenderWallets          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findLendersWithAcceptedBidsByProject($project->id_project);

        /** @var Wallet $wallet */
        foreach ($lenderWallets as $wallet) {
            if (false === $wallet->getIdClient()->isNaturalPerson()) {
                throw new \InvalidArgumentException('Bids of legal entity have been accepted. This is not allowed for IFP contracts');
            }

            $acceptedBids = $acceptedBidsRepository->findAcceptedBidsByLenderAndProject($wallet, $project->id_project);

            $this->loanManager->create($acceptedBids, $ifpContract);
        }
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    private function buildMinibonPrioritizedMixedLoanWithIfp(Projects $project): void
    {
        $miniBonContract = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->findOneBy(['label' => UnderlyingContract::CONTRACT_MINIBON]);
        if (null === $miniBonContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_MINIBON . ' does not exist.');
        }

        $ifpContract = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
        if (null === $ifpContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_IFP . ' does not exist.');
        }

        $acceptedBidsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AcceptedBids');
        $lenderWallets          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findLendersWithAcceptedBidsByProject($project);

        foreach ($lenderWallets as $wallet) {
            $acceptedBids       = $acceptedBidsRepository->findAcceptedBidsByLenderAndProject($wallet, $project);
            $acceptedBidsForIfp = [];

            foreach ($acceptedBids as $acceptedBid) {
                if (false === $wallet->getIdClient()->isNaturalPerson() || $this->cipManager->hasValidEvaluation($wallet->getIdClient(), $acceptedBid->getIdBid()->getAdded())) {
                    $this->loanManager->create([$acceptedBid], $miniBonContract);
                } else {
                    $acceptedBidsForIfp[] = $acceptedBid;
                }
            }

            if (false === empty($acceptedBidsForIfp)) {
                $this->loanManager->create($acceptedBidsForIfp, $ifpContract);
            }
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function treatFundFailed(\projects $project)
    {
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::FUNDING_KO, $project);

        $criteria     = ['idProject' => $project->id_project];
        $bids         = $bidRepository->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal  = $bidRepository->countBy($criteria);
        $treatedBidNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($iBidNbTotal . 'bids in total (project ' . $project->id_project . ')', [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'id_project' => $project->id_project
            ]);
        }

        foreach ($bids as $bid) {
            if ($bid) {
                $this->bidManager->reject($bid, false);
                $treatedBidNb++;
                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->debug($treatedBidNb . '/' . $iBidNbTotal . 'bids treated (project ' . $project->id_project . ')', [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_project' => $project->id_project
                    ]);
                }
            }
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Exception
     */
    public function createRepaymentSchedule(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        if (! $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }
        /** @var \repayment_type $repaymentType */
        $repaymentType = $this->entityManagerSimulator->getRepository('repayment_type');
        $repaymentType->get($product->id_repayment_type);

        switch ($repaymentType->label) {
            case \repayment_type::REPAYMENT_TYPE_AMORTIZATION:
                $this->createAmortizationRepaymentSchedule($project);
                return;
            case \repayment_type::REPAYMENT_TYPE_DEFERRED:
                $this->createDeferredRepaymentSchedule($project);
                return;
            default :
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    /**
     * @param \projects $project
     */
    private function createAmortizationRepaymentSchedule(\projects $project)
    {
        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        if ($project->status == \projects_status::FUNDE) {
            $lLoans = $oLoan->select('id_project = ' . $project->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info($iLoanNbTotal . ' in total (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
            }

            foreach ($lLoans as $l) {
                $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($l['id_lender']);
                $oLoan->get($l['id_loan']);

                $aRepaymentSchedule = array();
                foreach ($oLoan->getRepaymentSchedule() as $k => $e) {
                    $dateEcheance = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    $dateEcheance_emprunteur = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $k);
                    $dateEcheance_emprunteur = $this->workingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    $aRepaymentSchedule[] = array(
                        'id_lender'                => $l['id_lender'],
                        'id_project'               => $project->id_project,
                        'id_loan'                  => $l['id_loan'],
                        'ordre'                    => $k,
                        'montant'                  => bcmul($e['repayment'], 100),
                        'capital'                  => bcmul($e['capital'], 100),
                        'interets'                 => bcmul($e['interest'], 100),
                        'date_echeance'            => $dateEcheance,
                        'date_echeance_emprunteur' => $dateEcheance_emprunteur,
                        'added'                    => date('Y-m-d H:i:s'),
                        'updated'                  => date('Y-m-d H:i:s')
                    );
                }
                $oRepaymentSchedule->multiInsert($aRepaymentSchedule);

                $iTreatedLoanNb++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info(
                        $iTreatedLoanNb . '/' . $iLoanNbTotal . ' loans treated. ' . $k . ' repayment schedules created (project ' . $project->id_project . ' : ',
                        array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project)
                    );
                }
            }
        }
    }

    /**
     * @param \projects $project
     */
    private function createDeferredRepaymentSchedule(\projects $project)
    {
        /** @var \loans $loanEntity */
        $loanEntity = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $repaymentScheduleEntity */
        $repaymentScheduleEntity = $this->entityManagerSimulator->getRepository('echeanciers');

        if ($project->status == \projects_status::FUNDE) {
            $loans               = $loanEntity->select('id_project = ' . $project->id_project);
            $loansCount          = count($loans);
            $processedLoansCount = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    $loansCount . ' in total (project ' . $project->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                );
            }

            foreach ($loans as $loan) {
                $loanEntity->get($loan['id_loan']);

                $repayments = [];
                // @todo raw deferred duration = 12
                foreach ($loanEntity->getDeferredRepaymentSchedule(12) as $order => $repayment) {
                    $lenderRepaymentDate = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $order);
                    $lenderRepaymentDate = date('Y-m-d H:i:00', $lenderRepaymentDate);

                    $borrowerPaymentDate = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $order);
                    $borrowerPaymentDate = $this->workingDay->display_jours_ouvres($borrowerPaymentDate, 6);
                    $borrowerPaymentDate = date('Y-m-d H:i:00', $borrowerPaymentDate);

                    $repayments[] = [
                        'id_lender'                => $loan['id_lender'],
                        'id_project'               => $project->id_project,
                        'id_loan'                  => $loan['id_loan'],
                        'ordre'                    => $order,
                        'montant'                  => bcmul($repayment['repayment'], 100),
                        'capital'                  => bcmul($repayment['capital'], 100),
                        'interets'                 => bcmul($repayment['interest'], 100),
                        'date_echeance'            => $lenderRepaymentDate,
                        'date_echeance_emprunteur' => $borrowerPaymentDate,
                        'added'                    => date('Y-m-d H:i:s'),
                        'updated'                  => date('Y-m-d H:i:s')
                    ];
                }
                $repaymentScheduleEntity->multiInsert($repayments);

                $processedLoansCount++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info(
                        $processedLoansCount . '/' . $loansCount . ' loans treated. ' . $order . ' repayment schedules created (project ' . $project->id_project . ' : ',
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                    );
                }
            }
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Exception
     */
    public function createPaymentSchedule(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');

        if (false === $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }

        /** @var \repayment_type $repaymentType */
        $repaymentType = $this->entityManagerSimulator->getRepository('repayment_type');
        $repaymentType->get($product->id_repayment_type);

        switch ($repaymentType->label) {
            case \repayment_type::REPAYMENT_TYPE_AMORTIZATION:
                $this->createAmortizationPaymentSchedule($project);
                break;
            case \repayment_type::REPAYMENT_TYPE_DEFERRED:
                $this->createDeferredPaymentSchedule($project);
                break;
            default:
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    /**
     * @param \projects $project
     */
    private function createAmortizationPaymentSchedule(\projects $project)
    {
        /** @var \echeanciers_emprunteur $oPaymentSchedule */
        $oPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVAT    = $taxRate[TaxType::TYPE_VAT] / 100;

        $fAmount           = $project->amount;
        $iMonthNb          = $project->period;
        $aCommission       = \repayment::getRepaymentCommission($fAmount, $iMonthNb, round(bcdiv($project->commission_rate_repayment, 100, 4), 2), $fVAT);
        $aPaymentList      = $oRepaymentSchedule->getMonthlyScheduleByProject($project->id_project);
        $iPaymentsNbTotal  = count($aPaymentList);
        $iTreatedPaymentNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($iPaymentsNbTotal . ' borrower repayments in total (project ' . $project->id_project . ')', [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'id_project' => $project->id_project
            ]);
        }

        foreach ($aPaymentList as $iIndex => $aPayment) {
            $sPaymentDate = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $iIndex);
            $sPaymentDate = $this->workingDay->display_jours_ouvres($sPaymentDate, 6);
            $sPaymentDate = date('Y-m-d H:i', $sPaymentDate) . ':00';

            $oPaymentSchedule->id_project               = $project->id_project;
            $oPaymentSchedule->ordre                    = $iIndex;
            $oPaymentSchedule->montant                  = bcmul($aPayment['montant'], 100);
            $oPaymentSchedule->capital                  = bcmul($aPayment['capital'], 100);
            $oPaymentSchedule->interets                 = bcmul($aPayment['interets'], 100);
            $oPaymentSchedule->commission               = bcmul($aCommission['commission_monthly'], 100);
            $oPaymentSchedule->tva                      = bcmul($aCommission['vat_amount_monthly'], 100);
            $oPaymentSchedule->date_echeance_emprunteur = $sPaymentDate;
            $oPaymentSchedule->create();

            $iTreatedPaymentNb++;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    'Borrower repayment ' . $oPaymentSchedule->id_echeancier_emprunteur . ' created. ' . $iTreatedPaymentNb . '/' . $iPaymentsNbTotal . 'treated (project ' . $project->id_project . ')',
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project)
                );
            }
        }
    }

    /**
     * @param \projects $project
     */
    private function createDeferredPaymentSchedule(\projects $project)
    {
        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $vatRate = $taxRate[TaxType::TYPE_VAT] / 100;

        // @todo raw deferred duration
        $deferredDuration        = 12;
        $amount                  = $project->amount;
        $loanDuration            = $project->period;
        $commission              = \repayment::getDeferredRepaymentCommission($amount, $loanDuration, $deferredDuration, round(bcdiv($project->commission_rate_repayment, 100, 4), 2), $vatRate);
        $lenderRepaymentsSummary = $lenderRepaymentSchedule->getMonthlyScheduleByProject($project->id_project);
        $paymentsCount           = count($lenderRepaymentsSummary);
        $processedPayments       = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info(
                $paymentsCount . ' borrower repayments in total (project ' . $project->id_project . ')',
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
            );
        }

        foreach ($lenderRepaymentsSummary as $order => $lenderRepaymentSummary) {
            $borrowerPaymentSchedule->id_project               = $project->id_project;
            $borrowerPaymentSchedule->ordre                    = $order;
            $borrowerPaymentSchedule->montant                  = bcmul($lenderRepaymentSummary['montant'], 100);
            $borrowerPaymentSchedule->capital                  = bcmul($lenderRepaymentSummary['capital'], 100);
            $borrowerPaymentSchedule->interets                 = bcmul($lenderRepaymentSummary['interets'], 100);
            $borrowerPaymentSchedule->commission               = bcmul($commission['commission_monthly'], 100);
            $borrowerPaymentSchedule->tva                      = bcmul($commission['vat_amount_monthly'], 100);
            $borrowerPaymentSchedule->date_echeance_emprunteur = $lenderRepaymentSummary['date_echeance_emprunteur'];
            $borrowerPaymentSchedule->create();

            $processedPayments++;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    'Borrower repayment ' . $borrowerPaymentSchedule->id_echeancier_emprunteur . ' created. ' . $processedPayments . '/' . $paymentsCount . 'treated (project ' . $project->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                );
            }
        }
    }

    /**
     * @param \projects $project
     */
    public function markAsFunded(\projects $project)
    {
        if ($project->date_funded == '0000-00-00 00:00:00') {
            $oFunded    = new \DateTime();
            $oPublished = new \DateTime($project->date_publication);

            if ($oFunded < $oPublished) {
                $oFunded = $oPublished;
            }

            $project->date_funded = $oFunded->format('Y-m-d H:i:s');
            $project->update();

            $this->mailerManager->sendFundedToStaff($project);
        }
    }

    /**
     * @param \projects $project
     */
    public function saveInterestRate(\projects $project)
    {
        $project->interest_rate = $project->getAverageInterestRate(false);
        $project->update();
    }

    /**
     * @param \projects $project
     */
    private function insertNewProjectEmails(\projects $project): void
    {
        /** @var \clients $clientData */
        $clientData        = $this->entityManagerSimulator->getRepository('clients');
        $autobidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        /** @var \project_period $projectPeriodData */
        $projectPeriodData = $this->entityManagerSimulator->getRepository('project_period');

        $bidsRepository                        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $clientsGestionNotificationsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsGestionNotifications');
        $walletRepository                      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

        $companyEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($project->id_company);
        $projectPeriodData->getPeriod($project->period);

        $commonKeywords = [
            'companyName'     => $companyEntity->getName(),
            'projectAmount'   => $this->numberFormatter->format($project->amount),
            'projectDuration' => $project->period,
            'projectLink'     => $this->frontUrl . $this->router->generate('project_detail', ['projectSlug' => $project->slug])
        ];

        $autoBidSettings  = $autobidRepository->getSettings(null, $project->risk, $projectPeriodData->id_period, [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE]);
        $autoBidsAmount   = array_column($autoBidSettings, 'amount', 'id_lender');
        $autoBidsMinRate  = array_column($autoBidSettings, 'rate_min', 'id_lender');
        $autoBidsStatus   = array_column($autoBidSettings, 'status', 'id_lender');
        $projectRateRange = $this->bidManager->getProjectRateRange($project);
        $autolendUrl      = $this->frontUrl . $this->router->generate('autolend');
        $walletDepositUrl = $this->frontUrl . $this->router->generate('lender_wallet_deposit');

        $isProjectMinRateReached = $this->projectManager->isRateMinReached($project);

        $offset = 0;
        $limit  = 100;
        $this->logger->info('Insert publication emails for project: ' . $project->id_project, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

        while ($lenders = $clientData->selectPreteursByStatus(ClientsStatus::STATUS_VALIDATED, 'c.id_client ASC', $offset, $limit)) {
            $emailsInserted = 0;
            $offset         += $limit;
            $this->logger->info('Lenders retrieved: ' . count($lenders), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

            foreach ($lenders as $lender) {
                $wallet    = $walletRepository->getWalletByType($lender['id_client'], WalletType::LENDER);
                $keywords  = [];
                $mailType  = null;
                $bidEntity = null;

                $isClientEligible                          = $this->productManager->isClientEligible($wallet->getIdClient(), $project);
                $hasNewProjectOrAutobidNotificationSetting = $this->hasNewProjectOrAutobidNotificationSetting($wallet->getIdClient(), $clientsGestionNotificationsRepository);

                if ($isClientEligible && $hasNewProjectOrAutobidNotificationSetting) {
                    $autolendSettingsAdvises = '';
                    try {
                        $hasAutolendOn = $this->autobidSettingsManager->isOn($wallet->getIdClient());
                    } catch (\Exception $exception) {
                        $this->logger->error(
                            'Could not check Autolend activation state for lender ' . $wallet->getId() . '. No Autolend advice will be shown in the email. Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'id_project' => $project->id_project, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                        /** Do not include any advice about autolend in the email */
                        $hasAutolendOn = null;
                    }

                    try {
                        $bidEntity = $bidsRepository->findFirstAutoBidByLenderAndProject($wallet, $project->id_project);
                    } catch (NonUniqueResultException $exception) {
                        $this->logger->error(
                            'Could not get the placed autobid for the lender ' . $wallet->getId() . '. The email "nouveau-projet-autobid" will not be sent. Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'project' => $project->id_project, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                        continue;
                    }

                    if ($bidEntity instanceof Bids) {
                        $mailType = 'nouveau-projet-autobid';

                        $keywords['autoBidAmount'] = $this->currencyFormatter->formatCurrency(round(bcdiv($bidEntity->getAmount(), 100, 4), 2), 'EUR');
                        $autolendMinRate           = max($projectRateRange['rate_min'], $autoBidsMinRate[$wallet->getId()]);

                        $defaultFormatterFractionDigits = $this->numberFormatter->getAttribute(\NumberFormatter::MIN_FRACTION_DIGITS);
                        $this->numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 1);
                        $keywords['autoBidRate']     = $this->numberFormatter->format($bidEntity->getRate());
                        $keywords['autoLendMinRate'] = $this->numberFormatter->format($autolendMinRate);
                        $this->numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $defaultFormatterFractionDigits);

                        $keywords['availableBalance'] = $this->currencyFormatter->formatCurrency($wallet->getAvailableBalance(), 'EUR');
                        $keywords['autolendUrl']      = $autolendUrl;
                    } elseif (false === $isProjectMinRateReached && null === $bidEntity) {
                        $mailType = 'nouveau-projet';

                        if (true === $hasAutolendOn) {
                            if (isset($autoBidsStatus[$wallet->getId()])) {
                                switch ($autoBidsStatus[$wallet->getId()]) {
                                    case Autobid::STATUS_INACTIVE:
                                        $autolendSettingsAdvises = $this->translator->trans('email-nouveau-projet_autobid-setting-for-period-rate-off', ['%autolendUrl%' => $autolendUrl]);
                                        break;
                                    case Autobid::STATUS_ACTIVE:
                                        if (bccomp($wallet->getAvailableBalance(), $autoBidsAmount[$wallet->getId()]) < 0) {
                                            $autolendSettingsAdvises = $this->translator->trans('email-nouveau-projet_low-balance-for-autolend', ['%walletProvisionUrl%' => $walletDepositUrl]);
                                        }
                                        if (bccomp($autoBidsMinRate[$wallet->getId()], $projectRateRange['rate_max'], 2) > 0) {
                                            $autolendMinRateTooHigh  = $this->translator->trans('email-nouveau-projet_autobid-min-rate-too-high', ['%autolendUrl%' => $autolendUrl]);
                                            $autolendSettingsAdvises .= empty($autolendSettingsAdvises) ? $autolendMinRateTooHigh : '<br>' . $autolendMinRateTooHigh;
                                        }
                                        break;
                                    default:
                                        break;
                                }
                            }
                            $keywords['customAutolendContent'] = $this->getAutolendCustomMessage($autolendSettingsAdvises);
                        } elseif (false === $hasAutolendOn) {
                            $suggestAutolendActivation         = $this->translator->trans('email-nouveau-projet_suggest-autolend-activation', ['%autolendUrl%' => $autolendUrl]);
                            $keywords['customAutolendContent'] = $this->getAutolendCustomMessage($suggestAutolendActivation);
                        } else {
                            $keywords['customAutolendContent'] = '';
                        }
                    }
                    if (null !== $mailType) {
                        $publishingDate = new \DateTime($project->date_publication);
                        try {
                            $this
                                ->notificationManager
                                ->createEmailNotification(0, ClientsGestionTypeNotif::TYPE_NEW_PROJECT, $wallet->getIdClient()->getIdClient(), null, $project->id_project, null, true, $publishingDate);
                        } catch (OptimisticLockException $exception) {
                            $this->logger->warning(
                                'Could not insert the new project email notification for client ' . $wallet->getIdClient()->getIdClient() . '. Exception: ' . $exception->getMessage(),
                                ['method' => __METHOD__, 'id_project' => $project->id_project, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                            );
                        }
                        $keywords['firstName']     = $wallet->getIdClient()->getPrenom();
                        $keywords['lenderPattern'] = $wallet->getWireTransferPattern();
                        $message                   = $this->messageProvider->newMessage($mailType, $commonKeywords + $keywords);
                        try {
                            $message->setTo($lender['email']);
                            $message->setToSendAt($publishingDate);
                            $this->mailer->send($message);
                            ++$emailsInserted;
                        } catch (\Exception $exception) {
                            $this->logger->warning(
                                'Could not insert email ' . $mailType . ' - Exception: ' . $exception->getMessage(), [
                                'function'         => __FUNCTION__,
                                'class'            => __CLASS__,
                                'id_mail_template' => $message->getTemplateId(),
                                'id_client'        => $wallet->getIdClient()->getIdClient(),
                                'file'             => $exception->getFile(),
                                'line'             => $exception->getLine()
                            ]);
                        }
                    }
                }
            }
            $this->logger->info('Number of emails inserted = ' . $emailsInserted, ['method' => __METHOD__, 'id_project' => $project->id_project]);
        }
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function getAutolendCustomMessage(string $content): string
    {
        if (empty($content)) {
            return $content;
        }
        $customAutolendContent = '
            <table width="100%" border="1" cellspacing="0" cellpadding="5" bgcolor="d8b5ce" bordercolor="b20066">
                <tr>
                    <td class="text-primary text-center">' . $content . '</td>
                </tr>
            </table>';

        return $customAutolendContent;
    }

    /**
     * @param Clients          $client
     * @param EntityRepository $clientsGestionNotificationsRepository
     *
     * @return bool
     */
    private function hasNewProjectOrAutobidNotificationSetting(Clients $client, EntityRepository $clientsGestionNotificationsRepository): bool
    {
        $notificationSettings = $clientsGestionNotificationsRepository->findOneBy(
            [
                'idClient'      => $client->getIdClient(),
                'idNotif'       => [ClientsGestionTypeNotif::TYPE_NEW_PROJECT, ClientsGestionTypeNotif::TYPE_BID_PLACED],
                'immediatement' => 1
            ]
        );

        return null !== $notificationSettings;
    }

    /**
     * @param \projects $project
     */
    private function insertNewProjectNotification(\projects $project): void
    {
        /** @var \clients $clientData */
        $clientData = $this->entityManagerSimulator->getRepository('clients');

        /** @var WalletRepository $walletRepository */
        $walletRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

        $offset = 0;
        $limit  = 100;
        $this->logger->info('Insert new project notification for project: ' . $project->id_project, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

        while ($lenders = $clientData->selectPreteursByStatus(ClientsStatus::STATUS_VALIDATED, 'c.id_client ASC', $offset, $limit)) {
            $notificationsCount = 0;
            $offset             += $limit;
            $this->logger->info('Lenders retrieved: ' . count($lenders), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

            foreach ($lenders as $lender) {
                $wallet                 = $walletRepository->getWalletByType($lender['id_client'], WalletType::LENDER);
                $isClientEligible       = $this->productManager->isClientEligible($wallet->getIdClient(), $project);
                $newProjectNotification = null;

                if ($isClientEligible) {
                    $notificationsCount++;
                    $this->notificationManager->createNotification(Notifications::TYPE_NEW_PROJECT, $wallet->getIdClient()->getIdClient(), $project->id_project);
                }
            }
            $this->logger->info('Notifications inserted: ' . $notificationsCount, ['method' => __METHOD__, 'id_project' => $project->id_project]);
        }
    }

    /**
     * @param \projects $project
     *
     * @throws OptimisticLockException
     */
    private function sendAcceptedOrRejectedBidNotifications(\projects $project): void
    {
        /** @var \bids $bidData */
        $bidData = $this->entityManagerSimulator->getRepository('bids');

        $offset = 0;
        $limit  = 100;

        while ($bids = $bidData->getFirstProjectBidsByLender($project->id_project, $limit, $offset)) {
            foreach ($bids as $bid) {
                $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($bid['id_lender_account']);

                if (null !== $wallet && WalletType::LENDER === $wallet->getIdType()->getLabel()) {
                    if ($bid['min_status'] == Bids::STATUS_PENDING) {
                        $this->notificationManager->createNotification(
                            Notifications::TYPE_BID_PLACED,
                            $wallet->getIdClient()->getIdClient(),
                            $project->id_project,
                            $bid['amount'] / 100,
                            $bid['id_bid']
                        );
                    } elseif ($bid['min_status'] == Bids::STATUS_REJECTED) {
                        $this->notificationManager->create(
                            Notifications::TYPE_BID_REJECTED,
                            ($bid['id_autobid'] > 0) ? ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : ClientsGestionTypeNotif::TYPE_BID_REJECTED,
                            $wallet->getIdClient()->getIdClient(),
                            'sendBidRejected',
                            $project->id_project,
                            $bid['amount'] / 100,
                            $bid['id_bid']
                        );
                    }
                }
            }
            $offset += $limit;
        }
    }
}
