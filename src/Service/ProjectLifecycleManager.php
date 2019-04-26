<?php

namespace Unilend\Service;

use Doctrine\ORM\{EntityManagerInterface, EntityRepository, NonUniqueResultException, NoResultException, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{AcceptedBids, Autobid, Bids, BidsLogs, Clients, ClientsGestionNotifications, ClientsGestionTypeNotif, ClientsStatus, Notifications, Product, Projects, ProjectsStatus,
    RepaymentType, Settings, TaxType, UnderlyingContract, UnderlyingContractAttributeType, Users, Wallet, WalletType};
use Unilend\Service\Product\Contract\ContractAttributeManager;
use Unilend\Service\Product\ProductManager;
use Unilend\Service\Repayment\ProjectRepaymentScheduleManager;
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectLifecycleManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
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
    /** @var ProjectRepaymentScheduleManager */
    private $projectRepaymentScheduleManager;

    /**
     * @param EntityManagerSimulator          $entityManagerSimulator
     * @param EntityManagerInterface          $entityManager
     * @param BidManager                      $bidManager
     * @param LoanManager                     $loanManager
     * @param NotificationManager             $notificationManager
     * @param MailerManager                   $mailerManager
     * @param ProjectRateSettingsManager      $projectRateSettingsManager
     * @param ProductManager                  $productManager
     * @param ContractAttributeManager        $contractAttributeManager
     * @param ProjectStatusManager            $projectStatusManager
     * @param ProjectManager                  $projectManager
     * @param AutoBidSettingsManager          $autobidSettingsManager
     * @param TranslatorInterface             $translator
     * @param TemplateMessageProvider         $messageProvider
     * @param \Swift_Mailer                   $mailer
     * @param RouterInterface                 $router
     * @param string                          $frontUrl
     * @param \NumberFormatter                $numberFormatter
     * @param \NumberFormatter                $currencyFormatter
     * @param CIPManager                      $cipManager
     * @param ProjectRepaymentScheduleManager $projectRepaymentScheduleManager
     *
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManagerInterface $entityManager,
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
        CIPManager $cipManager,
        ProjectRepaymentScheduleManager $projectRepaymentScheduleManager
    )
    {
        $this->entityManagerSimulator          = $entityManagerSimulator;
        $this->entityManager                   = $entityManager;
        $this->bidManager                      = $bidManager;
        $this->loanManager                     = $loanManager;
        $this->notificationManager             = $notificationManager;
        $this->mailerManager                   = $mailerManager;
        $this->projectRateSettingsManager      = $projectRateSettingsManager;
        $this->productManager                  = $productManager;
        $this->contractAttributeManager        = $contractAttributeManager;
        $this->projectStatusManager            = $projectStatusManager;
        $this->projectManager                  = $projectManager;
        $this->autobidSettingsManager          = $autobidSettingsManager;
        $this->translator                      = $translator;
        $this->messageProvider                 = $messageProvider;
        $this->mailer                          = $mailer;
        $this->router                          = $router;
        $this->frontUrl                        = $frontUrl;
        $this->numberFormatter                 = $numberFormatter;
        $this->currencyFormatter               = $currencyFormatter;
        $this->cipManager                      = $cipManager;
        $this->projectRepaymentScheduleManager = $projectRepaymentScheduleManager;
    }

    /**
     * @required
     *
     * @param LoggerInterface|null $logger
     *
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Projects $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function prePublish(Projects $project): void
    {
        $this->autoBid($project);

        $isFunded = $this->projectManager->isFunded($project);

        if ($isFunded) {
            $this->markAsFunded($project);
        }

        $this->reBidAutoBidDeeply($project, false);

        $currentDate      = new \DateTime();
        $projectEndDate   = $this->projectManager->getProjectEndDate($project);
        $isRateMinReached = $this->projectManager->isRateMinReached($project);

        /**
         * "dateFin" is set here in order to be sure that we always use the same date in rejected bid emails
         * These emails are sent during publishing process in "sendAcceptedOrRejectedBidNotifications"
         */
        if ($isRateMinReached || $projectEndDate <= $currentDate) {
            $project->setDateFin($currentDate);
            $this->entityManager->flush($project);
        }

        if (true === $isFunded && false === $isRateMinReached) {
            $this->mailerManager->sendFundedToBorrower($project);
        }

        $this->insertNewProjectEmails($project);

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::STATUS_PUBLISHED, $project);
    }

    /**
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    public function publish(Projects $project): void
    {
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::STATUS_PUBLISHED, $project);

        $this->insertNewProjectNotification($project);

        try {
            $this->sendAcceptedOrRejectedBidNotifications($project);
        } catch (OptimisticLockException $exception) {
            $this->logger->error('Error while inserting new project notifications on the project: ' . $project->getIdProject() . '. Error: ' . $exception->getMessage(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Projects $project
     * @param bool     $sendNotification
     *
     * @return int[]
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function checkBids(Projects $project, bool $sendNotification): array
    {
        $temporarilyRejectedBids  = 0;
        $definitivelyRejectedBids = 0;
        $cumulativeBidsAmount     = 0;
        $logCheck                 = false;
        $logStart                 = new \DateTime();
        $bidRepository            = $this->entityManager->getRepository(Bids::class);
        $totalBidsAmount          = $bidRepository->getProjectTotalAmount($project);
        $projectAmount            = $project->getAmount();

        if ($totalBidsAmount >= $projectAmount) {
            $bids = $bidRepository->findBy(['idProject' => $project, 'status' => Bids::STATUS_PENDING], ['rate' => 'ASC', 'ordre' => 'ASC']);

            foreach ($bids as $bid) {
                if ($cumulativeBidsAmount < $projectAmount) {
                    $cumulativeBidsAmount = bcadd($cumulativeBidsAmount, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                } else {
                    $logCheck = true;

                    if (null === $bid->getAutobid()) {
                        $this->bidManager->reject($bid, $sendNotification);

                        ++$definitivelyRejectedBids;
                    } else {
                        // For a autobid, we don't send reject bid, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $bid->setStatus(Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID);
                        $this->entityManager->flush($bid);

                        ++$temporarilyRejectedBids;
                    }
                }
            }
        }

        if ($logCheck) {
            $log = new BidsLogs();
            $log
                ->setDebut($logStart)
                ->setFin(new \DateTime())
                ->setIdProject($project)
                ->setRateMax($bidRepository->getProjectMaxRate($project))
                ->setNbBidsKo($definitivelyRejectedBids + $temporarilyRejectedBids)
                ->setNbBidsEncours($bidRepository->countBy(['idProject' => $project, 'status' => Bids::STATUS_PENDING]))
                ->setTotalBids($bidRepository->countBy(['idProject' => $project]))
                ->setTotalBidsKo($bidRepository->countBy(['idProject' => $project, 'status' => Bids::STATUS_REJECTED]));

            $this->entityManager->persist($log);
            $this->entityManager->flush($log);
        }

        return [
            'definitivelyRejected' => $definitivelyRejectedBids,
            'temporarilyRejected'  => $temporarilyRejectedBids
        ];
    }

    /**
     * @param Projects $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function autoBid(Projects $project): void
    {
        switch ($project->getStatus()) {
            case ProjectsStatus::STATUS_REVIEW:
                $this->bidAllAutoBid($project);
                break;
            case ProjectsStatus::STATUS_PUBLISHED:
                $this->reBidAutoBid($project, true);
                break;
        }
    }

    /**
     * @param Projects $project
     *
     */
    private function bidAllAutoBid(Projects $project): void
    {
        $globalSetting = $this->entityManager
            ->getRepository(Settings::class)
            ->findOneBy(['type' => Settings::TYPE_AUTOBID_GLOBAL_SWITCH]);

        if (null === $globalSetting || empty($globalSetting->getValue())) {
            return;
        }

        $rateRange         = $this->bidManager->getProjectRateRange($project);
        $autoBidRepository = $this->entityManager->getRepository(Autobid::class);
        $autoBidSettings   = $autoBidRepository->getAutobidsForProject($project);

        foreach ($autoBidSettings as $autoBidSetting) {
            try {
                $this->bidManager->bid($autoBidSetting->getIdLender(), $project, $autoBidSetting->getAmount(), $rateRange['rate_max'], $autoBidSetting, false);
            } catch (\Exception $exception) {
                continue;
            }
        }

        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');
        $bid->shuffleAutoBidOrder($project->getIdProject());
    }

    /**
     * @param Projects $project
     * @param bool     $sendNotification
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    private function reBidAutoBid(Projects $project, bool $sendNotification): void
    {
        $rateStep = $this->entityManager->getRepository(Settings::class)
            ->findOneBy(['type' => Settings::TYPE_AUTOBID_STEP])
            ->getValue();

        $bidRepository      = $this->entityManager->getRepository(Bids::class);
        $currentRate        = bcsub($bidRepository->getProjectMaxRate($project), $rateStep, 1);
        $bidOrder           = null;
        $preCalculatedOrder = false;

        if (ProjectsStatus::STATUS_REVIEW === $project->getStatus()) {
            $preCalculatedOrder = true;
            $bidOrder           = $bidRepository->countBy(['idProject' => $project]);
            $bidOrder++;
        }

        while ($autoBids = $bidRepository->getAutoBids($project, Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID)) {
            foreach ($autoBids as $autoBid) {
                $bid = $this->bidManager->reBidAutoBidOrReject($autoBid, $currentRate, $bidOrder, $sendNotification);

                if ($preCalculatedOrder && Bids::STATUS_PENDING === $bid->getStatus()) {
                    $bidOrder = $bid->getOrdre() + 1;
                }
            }
        }
    }

    /**
     * @param Projects $project
     * @param bool     $sendNotification
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    private function reBidAutoBidDeeply(Projects $project, bool $sendNotification): void
    {
        $rejectedBids = $this->checkBids($project, $sendNotification);

        if (false === empty($rejectedBids['temporarilyRejected'])) {
            $this->reBidAutoBid($project, $sendNotification);
            $this->reBidAutoBidDeeply($project, $sendNotification);
        }
    }

    /**
     * @param Projects $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function buildLoans(Projects $project): void
    {
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::STATUS_PUBLISHED, $project);
        $this->reBidAutoBidDeeply($project, true);
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::STATUS_FUNDED, $project);
        $this->acceptBids($project);

        $contractTypes = array_column($this->productManager->getAvailableContracts($project->getIdProduct()), 'label');

        // @todo use array_intersect
        if (in_array(UnderlyingContract::CONTRACT_IFP, $contractTypes) && in_array(UnderlyingContract::CONTRACT_BDC, $contractTypes)) {
            $this->buildLoanIFPAndBDC($project);
        } elseif (in_array(UnderlyingContract::CONTRACT_IFP, $contractTypes) && in_array(UnderlyingContract::CONTRACT_MINIBON, $contractTypes)) {
            $this->buildMinibonPrioritizedMixedLoanWithIfp($project);
        } elseif (in_array(UnderlyingContract::CONTRACT_IFP, $contractTypes)) {
            $this->buildLoanIfp($project);
        }
    }

    /**
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    private function acceptBids(Projects $project): void
    {
        $bidBalance    = 0;
        $bidRepository = $this->entityManager->getRepository(Bids::class);
        $bids          = $bidRepository->findBy(
            ['idProject' => $project, 'status' => Bids::STATUS_PENDING],
            ['rate' => 'ASC', 'ordre' => 'ASC']
        );

        foreach ($bids as $bid) {
            if ($bid) {
                if ($bidBalance < $project->getAmount()) {
                    $bidAmount      = round(bcdiv($bid->getAmount(), 100, 4), 2);
                    $bidBalance     = round(bcadd($bidBalance, $bidAmount, 4), 2);
                    $acceptedAmount = null;

                    if ($bidBalance > $project->getAmount()) {
                        $cutAmount      = round(bcsub($bidBalance, $project->getAmount(), 4), 2);
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
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    private function buildLoanIfpAndMinibon(Projects $project): void
    {
        $this->buildIfpPrioritizedMixedLoan($project, UnderlyingContract::CONTRACT_MINIBON);
    }

    /**
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    private function buildLoanIFPAndBDC(Projects $project): void
    {
        $this->buildIfpPrioritizedMixedLoan($project, UnderlyingContract::CONTRACT_BDC);
    }

    /**
     * @param Projects $project
     * @param string   $additionalContractLabel
     *
     * @throws OptimisticLockException
     */
    private function buildIfpPrioritizedMixedLoan(Projects $project, string $additionalContractLabel): void
    {
        $ifpContract = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
        if (null === $ifpContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_IFP . ' does not exist.');
        }

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($ifpContract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        } else {
            $IfpLoanAmountMax = $contractAttrVars[0];
        }

        $additionalContract = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => $additionalContractLabel]);
        if (null === $additionalContract) {
            throw new \InvalidArgumentException('The contract ' . $additionalContractLabel . ' does not exist.');
        }

        $acceptedBidsRepository = $this->entityManager->getRepository(AcceptedBids::class);
        /** @var Wallet $wallet */
        foreach ($this->entityManager->getRepository(Wallet::class)->findLendersWithAcceptedBidsByProject($project) as $wallet) {
            $acceptedBids   = $acceptedBidsRepository->findAcceptedBidsByLenderAndProject($wallet, $project);
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
     * @param Projects $project
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    private function buildLoanIfp(Projects $project): void
    {
        $ifpContract = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
        if (null === $ifpContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_IFP . ' does not exist.');
        }

        $acceptedBidsRepository = $this->entityManager->getRepository(AcceptedBids::class);
        $lenderWallets          = $this->entityManager->getRepository(Wallet::class)->findLendersWithAcceptedBidsByProject($project);

        /** @var Wallet $wallet */
        foreach ($lenderWallets as $wallet) {
            if (false === $wallet->getIdClient()->isNaturalPerson()) {
                throw new \InvalidArgumentException('Bids of legal entity have been accepted. This is not allowed for IFP contracts');
            }

            $acceptedBids = $acceptedBidsRepository->findAcceptedBidsByLenderAndProject($wallet, $project);

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
        $miniBonContract = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => UnderlyingContract::CONTRACT_MINIBON]);
        if (null === $miniBonContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_MINIBON . ' does not exist.');
        }

        $ifpContract = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
        if (null === $ifpContract) {
            throw new \InvalidArgumentException('The contract ' . UnderlyingContract::CONTRACT_IFP . ' does not exist.');
        }

        $acceptedBidsRepository = $this->entityManager->getRepository(AcceptedBids::class);
        $lenderWallets          = $this->entityManager->getRepository(Wallet::class)->findLendersWithAcceptedBidsByProject($project);

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
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    public function treatFundFailed(Projects $project): void
    {
        $bidRepository = $this->entityManager->getRepository(Bids::class);

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::STATUS_CANCELLED, $project);

        $bids         = $bidRepository->findBy(['idProject' => $project], ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal  = $bidRepository->countBy(['idProject' => $project]);
        $treatedBidNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($iBidNbTotal . ' bids in total (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }

        foreach ($bids as $bid) {
            if ($bid) {
                $this->bidManager->reject($bid, false);
                $treatedBidNb++;
                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->debug($treatedBidNb . '/' . $iBidNbTotal . 'bids treated (project ' . $project->getIdProject() . ')', [
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__
                    ]);
                }
            }
        }
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function createRepaymentSchedule(Projects $project): void
    {
        $product = $this->entityManager->getRepository(Product::class)->find($project->getIdProduct());

        if (null === $product) {
            throw new \Exception('Invalid product ID ' . $project->getIdProduct() . ' found for project ID ' . $project->getIdProject());
        }

        if (null === $product->getIdRepaymentType()) {
            throw new \Exception('Undefined repayment schedule type for product ID ' . $product->getIdProduct());
        }

        switch ($product->getIdRepaymentType()->getLabel()) {
            case RepaymentType::REPAYMENT_TYPE_AMORTIZATION:
                $this->createAmortizationRepaymentSchedule($project);
                return;
            case RepaymentType::REPAYMENT_TYPE_DEFERRED:
                $this->createDeferredRepaymentSchedule($project);
                return;
            default:
                throw new \Exception('Unknown repayment schedule type ' . $product->getIdRepaymentType()->getLabel());
        }
    }

    /**
     * @param Projects $project
     */
    private function createAmortizationRepaymentSchedule(Projects $project): void
    {
        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        if ($project->getStatus() === ProjectsStatus::STATUS_FUNDED) {
            $lLoans = $oLoan->select('id_project = ' . $project->getIdProject());

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info($iLoanNbTotal . ' in total (project ' . $project->getIdProject() . ')', [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }

            foreach ($lLoans as $l) {
                $oLoan->get($l['id_loan']);

                $aRepaymentSchedule = [];
                foreach ($oLoan->getRepaymentSchedule() as $k => $e) {
                    $lenderRepaymentDate = $this->projectRepaymentScheduleManager->generateLenderMonthlyAmortizationDate($project->getDateFin(), $k);
                    $borrowerPaymentDate = $this->projectRepaymentScheduleManager->generateBorrowerMonthlyAmortizationDate($project->getDateFin(), $k);

                    $aRepaymentSchedule[] = [
                        'id_lender'                => $l['id_wallet'],
                        'id_project'               => $project->getIdProject(),
                        'id_loan'                  => $l['id_loan'],
                        'ordre'                    => $k,
                        'montant'                  => bcmul($e['repayment'], 100),
                        'capital'                  => bcmul($e['capital'], 100),
                        'interets'                 => bcmul($e['interest'], 100),
                        'date_echeance'            => $lenderRepaymentDate->format('Y-m-d H:i:00'),
                        'date_echeance_emprunteur' => $borrowerPaymentDate->format('Y-m-d H:i:00'),
                        'added'                    => date('Y-m-d H:i:s'),
                        'updated'                  => date('Y-m-d H:i:s')
                    ];
                }
                $oRepaymentSchedule->multiInsert($aRepaymentSchedule);

                $iTreatedLoanNb++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info($iTreatedLoanNb . '/' . $iLoanNbTotal . ' loans treated. ' . $k . ' repayment schedules created (project ' . $project->getIdProject() . ' : ', [
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__
                    ]);
                }
            }
        }
    }

    /**
     * @param Projects $project
     */
    private function createDeferredRepaymentSchedule(Projects $project): void
    {
        /** @var \loans $loanEntity */
        $loanEntity = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $repaymentScheduleEntity */
        $repaymentScheduleEntity = $this->entityManagerSimulator->getRepository('echeanciers');

        if ($project->getStatus() === ProjectsStatus::STATUS_FUNDED) {
            $loans               = $loanEntity->select('id_project = ' . $project->getIdProject());
            $loansCount          = count($loans);
            $processedLoansCount = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info($loansCount . ' in total (project ' . $project->getIdProject() . ')', [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }

            foreach ($loans as $loan) {
                $loanEntity->get($loan['id_loan']);

                $repayments = [];
                // @todo raw deferred duration = 12
                foreach ($loanEntity->getDeferredRepaymentSchedule(12) as $order => $repayment) {
                    $lenderRepaymentDate = $this->projectRepaymentScheduleManager->generateLenderMonthlyAmortizationDate($project->getDateFin(), $order);
                    $borrowerPaymentDate = $this->projectRepaymentScheduleManager->generateBorrowerMonthlyAmortizationDate($project->getDateFin(), $order);

                    $repayments[] = [
                        'id_lender'                => $loan['id_wallet'],
                        'id_project'               => $project->getIdProject(),
                        'id_loan'                  => $loan['id_loan'],
                        'ordre'                    => $order,
                        'montant'                  => bcmul($repayment['repayment'], 100),
                        'capital'                  => bcmul($repayment['capital'], 100),
                        'interets'                 => bcmul($repayment['interest'], 100),
                        'date_echeance'            => $lenderRepaymentDate->format('Y-m-d H:i:00'),
                        'date_echeance_emprunteur' => $borrowerPaymentDate->format('Y-m-d H:i:00'),
                        'added'                    => date('Y-m-d H:i:s'),
                        'updated'                  => date('Y-m-d H:i:s')
                    ];
                }
                $repaymentScheduleEntity->multiInsert($repayments);

                $processedLoansCount++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info($processedLoansCount . '/' . $loansCount . ' loans treated. ' . $order . ' repayment schedules created (project ' . $project->getIdProject() . ' : ', [
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__
                    ]);
                }
            }
        }
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function createPaymentSchedule(Projects $project): void
    {
        $product = $this->entityManager->getRepository(Product::class)->find($project->getIdProduct());

        if (null === $product) {
            throw new \Exception('Invalid product ID ' . $project->getIdProduct() . ' found for project ID ' . $project->getIdProject());
        }

        if (null === $product->getIdRepaymentType()) {
            throw new \Exception('Undefined repayment schedule type for product ID ' . $product->getIdProduct());
        }

        switch ($product->getIdRepaymentType()->getLabel()) {
            case RepaymentType::REPAYMENT_TYPE_AMORTIZATION:
                $this->createAmortizationPaymentSchedule($project);
                return;
            case RepaymentType::REPAYMENT_TYPE_DEFERRED:
                $this->createDeferredPaymentSchedule($project);
                return;
            default:
                throw new \Exception('Unknown repayment schedule type ' . $product->getIdRepaymentType()->getLabel());
        }
    }

    /**
     * @param Projects $project
     */
    private function createAmortizationPaymentSchedule(Projects $project): void
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');
        $taxRate = $taxType->getTaxRateByCountry('fr');
        $vatRate = $taxRate[TaxType::TYPE_VAT] / 100;

        $amount                  = $project->getAmount();
        $loanDuration            = $project->getPeriod();
        $commission              = \repayment::getRepaymentCommission($amount, $loanDuration, round(bcdiv($project->getCommissionRateRepayment(), 100, 4), 2), $vatRate);
        $lenderRepaymentsSummary = $repaymentSchedule->getMonthlyScheduleByProject($project->getIdProject());
        $paymentsCount           = count($lenderRepaymentsSummary);
        $processedPayments       = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($paymentsCount . ' borrower repayments in total (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }

        foreach ($lenderRepaymentsSummary as $order => $lenderRepaymentSummary) {
            $paymentDate = $this->projectRepaymentScheduleManager->generateBorrowerMonthlyAmortizationDate($project->getDateFin(), $order);
            /** @var \echeanciers_emprunteur $paymentSchedule */
            $paymentSchedule                           = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
            $paymentSchedule->id_project               = $project->getIdProject();
            $paymentSchedule->ordre                    = $order;
            $paymentSchedule->montant                  = bcmul($lenderRepaymentSummary['montant'], 100);
            $paymentSchedule->capital                  = bcmul($lenderRepaymentSummary['capital'], 100);
            $paymentSchedule->interets                 = bcmul($lenderRepaymentSummary['interets'], 100);
            $paymentSchedule->commission               = bcmul($commission['commission_monthly'], 100);
            $paymentSchedule->tva                      = bcmul($commission['vat_amount_monthly'], 100);
            $paymentSchedule->date_echeance_emprunteur = $paymentDate->format('Y-m-d H:i:00');
            $paymentSchedule->create();

            $processedPayments++;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info('Borrower repayment ' . $paymentSchedule->id_echeancier_emprunteur . ' created. ' . $processedPayments . '/' . $paymentsCount . 'treated (project ' . $project->getIdProject() . ')', [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }
        }
    }

    /**
     * @param Projects $project
     */
    private function createDeferredPaymentSchedule(Projects $project): void
    {
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');
        $taxRate = $taxType->getTaxRateByCountry('fr');
        $vatRate = $taxRate[TaxType::TYPE_VAT] / 100;

        // @todo raw deferred duration
        $deferredDuration        = 12;
        $amount                  = $project->getAmount();
        $loanDuration            = $project->getPeriod();
        $commission              = \repayment::getDeferredRepaymentCommission($amount, $loanDuration, $deferredDuration, round(bcdiv($project->getCommissionRateRepayment(), 100, 4), 2), $vatRate);
        $lenderRepaymentsSummary = $lenderRepaymentSchedule->getMonthlyScheduleByProject($project->getIdProject());
        $paymentsCount           = count($lenderRepaymentsSummary);
        $processedPayments       = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($paymentsCount . ' borrower repayments in total (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }

        foreach ($lenderRepaymentsSummary as $order => $lenderRepaymentSummary) {
            $paymentDate = $this->projectRepaymentScheduleManager->generateBorrowerMonthlyAmortizationDate($project->getDateFin(), $order);
            /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
            $borrowerPaymentSchedule                           = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
            $borrowerPaymentSchedule->id_project               = $project->getIdProject();
            $borrowerPaymentSchedule->ordre                    = $order;
            $borrowerPaymentSchedule->montant                  = bcmul($lenderRepaymentSummary['montant'], 100);
            $borrowerPaymentSchedule->capital                  = bcmul($lenderRepaymentSummary['capital'], 100);
            $borrowerPaymentSchedule->interets                 = bcmul($lenderRepaymentSummary['interets'], 100);
            $borrowerPaymentSchedule->commission               = bcmul($commission['commission_monthly'], 100);
            $borrowerPaymentSchedule->tva                      = bcmul($commission['vat_amount_monthly'], 100);
            $borrowerPaymentSchedule->date_echeance_emprunteur = $paymentDate->format('Y-m-d H:i:00');
            $borrowerPaymentSchedule->create();

            $processedPayments++;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info('Borrower repayment ' . $borrowerPaymentSchedule->id_echeancier_emprunteur . ' created. ' . $processedPayments . '/' . $paymentsCount . 'treated (project ' . $project->getIdProject() . ')', [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }
        }
    }

    /**
     * @param Projects $project
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function markAsFunded(Projects $project): void
    {
        if (empty($project->getDateFunded())) {
            $funded    = new \DateTime();
            $published = $project->getDatePublication();

            if ($funded < $published) {
                $funded = $published;
            }

            $project->setDateFunded($funded);

            $this->entityManager->flush($project);

            $this->mailerManager->sendFundedToStaff($project);
        }
    }

    /**
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    public function saveInterestRate(Projects $project): void
    {
        $interestRate = $this->entityManager
            ->getRepository(Projects::class)
            ->getAverageInterestRate($project, false);

        $project->setInterestRate($interestRate);
        $this->entityManager->flush($project);
    }

    /**
     * @param Projects $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function insertNewProjectEmails(Projects $project): void
    {
        /** @var \clients $clientData */
        $clientData                            = $this->entityManagerSimulator->getRepository('clients');
        $autobidRepository                     = $this->entityManager->getRepository(Autobid::class);
        $bidsRepository                        = $this->entityManager->getRepository(Bids::class);
        $clientsGestionNotificationsRepository = $this->entityManager->getRepository(ClientsGestionNotifications::class);
        $walletRepository                      = $this->entityManager->getRepository(Wallet::class);

        $commonKeywords = [
            'companyName'     => $project->getIdCompany()->getName(),
            'projectAmount'   => $this->numberFormatter->format($project->getAmount()),
            'projectDuration' => $project->getPeriod(),
            'projectLink'     => $this->frontUrl . $this->router->generate('project_detail', ['projectSlug' => $project->getSlug()])
        ];

        /** @var \project_period $projectPeriodData */
        $projectPeriodData = $this->entityManagerSimulator->getRepository('project_period');
        $projectPeriodData->getPeriod($project->getPeriod());

        $autoBidSettings  = $autobidRepository->getSettings(null, $project->getRisk(), $projectPeriodData->id_period, [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE]);
        $autoBidsAmount   = array_column($autoBidSettings, 'amount', 'id_lender');
        $autoBidsMinRate  = array_column($autoBidSettings, 'rate_min', 'id_lender');
        $autoBidsStatus   = array_column($autoBidSettings, 'status', 'id_lender');
        $projectRateRange = $this->bidManager->getProjectRateRange($project);
        $autolendUrl      = $this->frontUrl . $this->router->generate('autolend');
        $walletDepositUrl = $this->frontUrl . $this->router->generate('lender_wallet_deposit');

        $isProjectMinRateReached = $this->projectManager->isRateMinReached($project);

        $offset = 0;
        $limit  = 100;

        $this->logger->info('Insert publication emails for project ' . $project->getIdProject(), [
            'id_project' => $project->getIdProject(),
            'class'      => __CLASS__,
            'function'   => __FUNCTION__
        ]);

        while ($lenders = $clientData->selectPreteursByStatus(ClientsStatus::STATUS_VALIDATED, 'c.id_client ASC', $offset, $limit)) {
            $emailsInserted = 0;
            $offset         += $limit;

            $this->logger->info('Lenders retrieved: ' . count($lenders), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);

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
                        $this->logger->error('Could not check Autolend activation state for lender ' . $wallet->getId() . '. No Autolend advice will be shown in the email. Error: ' . $exception->getMessage(), [
                            'id_project' => $project->getIdProject(),
                            'class'      => __CLASS__,
                            'function'   => __FUNCTION__,
                            'file'       => $exception->getFile(),
                            'line'       => $exception->getLine()
                        ]);
                        /** Do not include any advice about autolend in the email */
                        $hasAutolendOn = null;
                    }

                    try {
                        $bidEntity = $bidsRepository->findFirstAutoBidByLenderAndProject($wallet, $project->getIdProject());
                    } catch (NonUniqueResultException $exception) {
                        $this->logger->error('Could not get the placed autobid for the lender ' . $wallet->getId() . '. The email "nouveau-projet-autobid" will not be sent. Error: ' . $exception->getMessage(), [
                            'id_project' => $project->getIdProject(),
                            'class'      => __CLASS__,
                            'function'   => __FUNCTION__,
                            'file'       => $exception->getFile(),
                            'line'       => $exception->getLine()
                        ]);
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
                        try {
                            $this
                                ->notificationManager
                                ->createEmailNotification(ClientsGestionTypeNotif::TYPE_NEW_PROJECT, $wallet->getIdClient()->getIdClient(), null, null, $project->getIdProject(), null, true, $project->getDatePublication());
                        } catch (OptimisticLockException $exception) {
                            $this->logger->warning('Could not insert the new project email notification for client ' . $wallet->getIdClient()->getIdClient() . '. Exception: ' . $exception->getMessage(), [
                                'id_project' => $project->getIdProject(),
                                'class'      => __CLASS__,
                                'function'   => __FUNCTION__,
                                'file'       => $exception->getFile(),
                                'line'       => $exception->getLine()
                            ]);
                        }
                        $keywords['firstName']     = $wallet->getIdClient()->getFirstName();
                        $keywords['lenderPattern'] = $wallet->getWireTransferPattern();
                        $message                   = $this->messageProvider->newMessage($mailType, $commonKeywords + $keywords);
                        try {
                            $message->setTo($lender['email']);
                            $message->setToSendAt($project->getDatePublication());
                            $this->mailer->send($message);
                            ++$emailsInserted;
                        } catch (\Exception $exception) {
                            $this->logger->warning(
                                'Could not insert email ' . $mailType . ' - Exception: ' . $exception->getMessage(), [
                                'id_mail_template' => $message->getTemplateId(),
                                'id_client'        => $wallet->getIdClient()->getIdClient(),
                                'id_project'       => $project->getIdProject(),
                                'class'            => __CLASS__,
                                'function'         => __FUNCTION__,
                                'file'             => $exception->getFile(),
                                'line'             => $exception->getLine()
                            ]);
                        }
                    }
                }
            }
            $this->logger->info('Number of emails inserted: ' . $emailsInserted, [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
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
            <table width="100%" border="1" cellspacing="0" cellpadding="5" bgcolor="d8b5ce" bordercolor="2bc9af">
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
     * @param Projects $project
     */
    private function insertNewProjectNotification(Projects $project): void
    {
        /** @var \clients $clientData */
        $clientData       = $this->entityManagerSimulator->getRepository('clients');
        $walletRepository = $this->entityManager->getRepository(Wallet::class);

        $offset = 0;
        $limit  = 100;

        $this->logger->info('Insert new project notification for project: ' . $project->getIdProject(), [
            'id_project' => $project->getIdProject(),
            'class'      => __CLASS__,
            'function'   => __FUNCTION__
        ]);

        while ($lenders = $clientData->selectPreteursByStatus(ClientsStatus::STATUS_VALIDATED, 'c.id_client ASC', $offset, $limit)) {
            $notificationsCount = 0;
            $offset             += $limit;

            $this->logger->info('Lenders retrieved: ' . count($lenders), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);

            foreach ($lenders as $lender) {
                $wallet                 = $walletRepository->getWalletByType($lender['id_client'], WalletType::LENDER);
                $isClientEligible       = $this->productManager->isClientEligible($wallet->getIdClient(), $project);
                $newProjectNotification = null;

                if ($isClientEligible) {
                    $notificationsCount++;
                    $this->notificationManager->createNotification(Notifications::TYPE_NEW_PROJECT, $wallet->getIdClient()->getIdClient(), $project->getIdProject());
                }
            }

            $this->logger->info('Notifications inserted: ' . $notificationsCount, [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Projects $project
     *
     * @throws OptimisticLockException
     */
    private function sendAcceptedOrRejectedBidNotifications(Projects $project): void
    {
        /** @var \bids $bidData */
        $bidData          = $this->entityManagerSimulator->getRepository('bids');
        $walletRepository = $this->entityManager->getRepository(Wallet::class);

        $offset = 0;
        $limit  = 100;

        while ($bids = $bidData->getFirstProjectBidsByLender($project->getIdProject(), $limit, $offset)) {
            foreach ($bids as $bid) {
                $wallet = $walletRepository->find($bid['id_wallet']);

                if (null !== $wallet && WalletType::LENDER === $wallet->getIdType()->getLabel()) {
                    if ($bid['min_status'] == Bids::STATUS_PENDING) {
                        $this->notificationManager->createNotification(
                            Notifications::TYPE_BID_PLACED,
                            $wallet->getIdClient()->getIdClient(),
                            $project->getIdProject(),
                            $bid['amount'] / 100,
                            $bid['id_bid']
                        );
                    } elseif ($bid['min_status'] == Bids::STATUS_REJECTED) {
                        $this->notificationManager->create(
                            Notifications::TYPE_BID_REJECTED,
                            ($bid['id_autobid'] > 0) ? ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : ClientsGestionTypeNotif::TYPE_BID_REJECTED,
                            $wallet->getIdClient()->getIdClient(),
                            'sendBidRejected',
                            $project->getIdProject(),
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
