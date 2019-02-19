<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{BorrowingMotive, Companies, CompanyRating, Partner, ProjectRejectionReason, Projects, ProjectsStatus, ProjectStatusHistoryReason, TaxType, Users};
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\EligibilityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\SourceManager;

class ProjectRequestManager
{
    const EXCEPTION_CODE_INVALID_SIREN    = 100;
    const EXCEPTION_CODE_INVALID_EMAIL    = 101;
    const EXCEPTION_CODE_INVALID_AMOUNT   = 102;
    const EXCEPTION_CODE_INVALID_DURATION = 103;
    const EXCEPTION_CODE_INVALID_REASON   = 104;

    const DEFAULT_PROJECT_AMOUNT = 10000;
    const MINIMUM_REVENUE        = 100000;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ClientCreationManager */
    private $clientCreationManager;
    /** @var SourceManager */
    private $sourceManager;
    /** @var PartnerManager */
    private $partnerManager;
    /** @var EligibilityManager */
    private $eligibilityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var  PartnerProductManager */
    private $partnerProductManager;
    /** @var CompanyManager */
    private $companyManager;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManagerInterface $entityManager
     * @param ClientCreationManager  $clientCreationManager
     * @param SourceManager          $sourceManager
     * @param PartnerManager         $partnerManager
     * @param EligibilityManager     $eligibilityManager
     * @param LoggerInterface        $logger
     * @param PartnerProductManager  $partnerProductManager
     * @param CompanyManager         $companyManager
     * @param ProjectStatusManager   $projectStatusManager
     * @param ProjectManager         $projectManager
     * @param TranslatorInterface    $translator
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManagerInterface $entityManager,
        ClientCreationManager $clientCreationManager,
        SourceManager $sourceManager,
        PartnerManager $partnerManager,
        EligibilityManager $eligibilityManager,
        LoggerInterface $logger,
        PartnerProductManager $partnerProductManager,
        CompanyManager $companyManager,
        ProjectStatusManager $projectStatusManager,
        ProjectManager $projectManager,
        TranslatorInterface $translator
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->clientCreationManager  = $clientCreationManager;
        $this->sourceManager          = $sourceManager;
        $this->partnerManager         = $partnerManager;
        $this->eligibilityManager     = $eligibilityManager;
        $this->logger                 = $logger;
        $this->partnerProductManager  = $partnerProductManager;
        $this->companyManager         = $companyManager;
        $this->projectStatusManager   = $projectStatusManager;
        $this->projectManager         = $projectManager;
        $this->translator             = $translator;
    }

    /**
     * @return float
     */
    public function getMonthlyRateEstimate()
    {
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');

        return round($projects->getGlobalAverageRateOfFundedProjects(50), 1);
    }

    /**
     * @param int   $amount
     * @param int   $period
     * @param float $estimatedRate
     *
     * @return float
     */
    public function getMonthlyPaymentEstimate($amount, $period, $estimatedRate)
    {
        /** @var \PHPExcel_Calculation_Financial $oFinancial */
        $oFinancial = new \PHPExcel_Calculation_Financial();

        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');
        $taxType->get(TaxType::TYPE_VAT);
        $fVATRate = $taxType->rate / 100;

        $fCommission    = ($oFinancial->PMT(round(bcdiv(Projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 4), 2) / 12, $period, -$amount) - $oFinancial->PMT(0, $period, -$amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, -$amount) + $fCommission);

        return $monthlyPayment;
    }

    /**
     * @param Users       $user
     * @param Partner     $partner
     * @param int         $status
     * @param string|null $amount
     * @param string|null $siren
     * @param string|null $siret
     * @param string|null $companyName
     * @param string|null $email
     * @param int|null    $durationInMonth
     * @param int|null    $reason
     *
     * @return Projects
     * @throws \Exception
     */
    public function newProject(
        Users $user,
        Partner $partner,
        int $status,
        ?string $amount = null,
        ?string $siren = null,
        ?string $siret = null,
        ?string $companyName = null,
        ?string $email = null,
        ?int $durationInMonth = null,
        ?int $reason = null
    )
    {
        if (null !== $email && false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email', self::EXCEPTION_CODE_INVALID_EMAIL);
        }

        if (null !== $siren) {
            $siren = $this->validateSiren($siren);
            if (false === $siren) {
                throw new \InvalidArgumentException('Invalid SIREN = ' . $siren, self::EXCEPTION_CODE_INVALID_SIREN);
            }

        } elseif (Users::USER_ID_FRONT === $user->getIdUser() && BorrowingMotive::ID_MOTIVE_FRANCHISER_CREATION !== $reason) {
            throw new \InvalidArgumentException('Invalid SIREN = ' . $siren, self::EXCEPTION_CODE_INVALID_SIREN);
        }

        if (null !== $siret) {
            $siret = $this->validateSiret($siret);
            if (false === $siret) {
                $siret = null;
            }
        }

        if (null !== $amount) {
            $amount        = preg_replace(['/\s/', '/€/'], '', $amount);
            $minimumAmount = $this->projectManager->getMinProjectAmount();
            $maximumAmount = $this->projectManager->getMaxProjectAmount();

            if (empty($amount) || false === filter_var($amount, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimumAmount, 'max_range' => $maximumAmount]])) {
                throw new \InvalidArgumentException('Invalid amount = ' . $amount, self::EXCEPTION_CODE_INVALID_AMOUNT);
            }
        }

        if (null !== $durationInMonth && (empty($durationInMonth) || false === filter_var($durationInMonth, FILTER_VALIDATE_INT))) {
            throw new \InvalidArgumentException('Invalid duration', self::EXCEPTION_CODE_INVALID_DURATION);
        }

        if (null !== $reason) {
            if (false === empty($reason) && filter_var($reason, FILTER_VALIDATE_INT)) {
                $reason = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->find($reason);
            }

            if (false === $reason instanceof BorrowingMotive) {
                throw new \InvalidArgumentException('Invalid reason', self::EXCEPTION_CODE_INVALID_REASON);
            }
        }

        $this->entityManager->beginTransaction();

        try {
            $company = $this->companyManager->createBorrowerCompany($user, $email, $siren, $siret, $companyName);
            $client  = $company->getIdClientOwner();
            $client
                ->setSource($this->sourceManager->getSource(SourceManager::SOURCE1))
                ->setSource2($this->sourceManager->getSource(SourceManager::SOURCE2))
                ->setSource3($this->sourceManager->getSource(SourceManager::SOURCE3))
                ->setSlugOrigine($this->sourceManager->getSource(SourceManager::ENTRY_SLUG));

            $this->entityManager->flush($client);

            $project = $this->createProjectByCompany($user, $company, $partner, $status, $amount, $durationInMonth, $reason);

            $this->entityManager->commit();

            return $project;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('An error occurred while creating project. Error message : ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
            throw $exception;
        }
    }

    /**
     * @param Users                $user
     * @param Companies            $company
     * @param Partner              $partner
     * @param int                  $status
     * @param int|null             $amount
     * @param int|null             $duration
     * @param BorrowingMotive|null $reason
     * @param string|null          $comments
     *
     * @return Projects
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createProjectByCompany(
        Users $user,
        Companies $company,
        Partner $partner,
        int $status,
        ?int $amount = null,
        ?int $duration = null,
        ?BorrowingMotive $reason = null,
        ?string $comments = null
    ): Projects
    {
        $createdInBO = $user->getIdUser() === Users::USER_ID_FRONT ? false : true;
        $reasonId    = null === $reason ? null : $reason->getIdMotive();

        $project = new Projects();
        $project
            ->setIdCompany($company)
            ->setAmount($amount)
            ->setPeriod($duration)
            ->setIdBorrowingMotive($reasonId)
            ->setComments($comments)
            ->setStatus($status)
            ->setIdPartner($partner)
            ->setCommissionRateFunds(Projects::DEFAULT_COMMISSION_RATE_FUNDS)
            ->setCommissionRateRepayment(Projects::DEFAULT_COMMISSION_RATE_REPAYMENT)
            ->setCreateBo($createdInBO)
            ->setDisplay(Projects::AUTO_REPAYMENT_ON);

        $this->entityManager->persist($project);

        $this->entityManager->flush($project);

        $this->projectStatusManager->addProjectStatus($user, $status, $project);

        return $project;
    }

    /**
     * @param Companies     $company
     * @param int           $userId
     * @param Projects|null $project
     *
     * @return array
     */
    public function checkCompanyRisk(Companies $company, $userId, Projects $project = null)
    {
        /** @var \company_rating $companyRating */
        $companyRating                  = $this->entityManagerSimulator->getRepository('company_rating');
        $companyRatingHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $lastCompanyRatingHistory       = $companyRatingHistoryRepository->findOneBy(
            ['idCompany' => $company->getIdCompany()],
            ['added' => 'DESC']
        );

        /** @var \company_rating_history $companyRatingHistory */
        $companyRatingHistory             = $this->entityManagerSimulator->getRepository('company_rating_history');
        $companyRatingHistory->id_company = $company->getIdCompany();
        $companyRatingHistory->id_user    = $userId;
        $companyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $companyRatingHistory->create();

        if (null !== $lastCompanyRatingHistory) {
            foreach ($companyRating->getHistoryRatingsByType($lastCompanyRatingHistory->getIdCompanyRatingHistory()) as $rating => $value) {
                if (false === in_array($rating, CompanyRating::AUTOMATIC_RATING_TYPES)) {
                    $companyRating->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
                    $companyRating->type                      = $rating;
                    $companyRating->value                     = $value['value'];
                    $companyRating->create();
                }
            }
        }

        if ($project instanceof Projects) {
            return $this->eligibilityManager->checkProjectEligibility($project);
        }

        return $this->eligibilityManager->checkCompanyEligibility($company);
    }

    /**
     * @param \projects|Projects $projectToCheck
     * @param int                $userId
     *
     * @return null|array
     */
    public function checkProjectRisk($projectToCheck, int $userId): ?array
    {
        return null;

        if ($projectToCheck instanceof \projects) {
            $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectToCheck->id_project);
        } else {
            $project = $projectToCheck;
        }
        $company     = $project->getIdCompany();
        $eligibility = $this->checkCompanyRisk($company, $userId, $project);

        $companyRatingHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $lastCompanyRatingHistory       = $companyRatingHistoryRepository->findOneBy(
            ['idCompany' => $company->getIdCompany()],
            ['added' => 'DESC']
        );

        $lastBalance = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompaniesBilans')
            ->findBy(['idCompany' => $company->getIdCompany()], ['clotureExerciceFiscal' => 'DESC'], 1);

        if (false === empty($lastBalance)) {
            $project->setIdDernierBilan($lastBalance[0]->getIdBilan());
        }
        $balanceCount = null === $company->getDateCreation() ? 0 : $company->getDateCreation()->diff(new \DateTime())->y;
        $project
            ->setBalanceCount($balanceCount)
            ->setIdCompanyRatingHistory($lastCompanyRatingHistory->getIdCompanyRatingHistory());
        try {
            $this->entityManager->flush($project);
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not save balance count and last rating history on project: ' . $project->getIdProject() . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        if (is_array($eligibility) && false === empty($eligibility)) {
            return $this->addRejectionProjectStatus($eligibility[0], $project, $userId);
        }

        return null;
    }

    /**
     * @param string             $motive
     * @param \projects|Projects $project
     * @param int                $userId
     *
     * @return array|null
     */
    public function addRejectionProjectStatus(string $motive, $project, int $userId): ?array
    {
        $status = substr($motive, 0, strlen(ProjectsStatus::UNEXPECTED_RESPONSE)) === ProjectsStatus::UNEXPECTED_RESPONSE
            ? ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION
            : ProjectsStatus::NOT_ELIGIBLE;

        switch ($status) {
            case ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION:
                $this->projectStatusManager->addProjectStatus($userId, $status, $project, 0, $motive);
                break;
            default:
                $rejectionReasons = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRejectionReason')
                    ->findBy(['label' => $motive]);
                try {
                    $result = $this->projectStatusManager->rejectProject($project, ProjectsStatus::NOT_ELIGIBLE, $rejectionReasons, $userId);

                    if (false === $result) {
                        return null;
                    }
                } catch (\Exception $exception) {
                    $this->logger->error('Could not reject project: ' . $project->getIdProject() . '. Error: ' . $exception->getMessage(), [
                        'id_project'       => $project->getIdProject(),
                        'rejection_reason' => $motive,
                        'class'            => __CLASS__,
                        'function'         => __FUNCTION__,
                        'file'             => $exception->getFile(),
                        'line'             => $exception->getLine()
                    ]);

                    return null;
                }
                break;
        }

        return ['motive' => $motive, 'status' => $status];
    }

    /**
     * @param \projects|Projects $project
     * @param int                $userId
     * @param boolean            $addProjectStatus
     *
     * @return int
     */
    public function assignEligiblePartnerProduct($project, int $userId, $addProjectStatus = false): int
    {
        try {
            if ($project instanceof \projects) {
                $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
            }

            if (false === empty($project->getIdPartner())) {
                $products = $this->partnerProductManager->findEligibleProducts($project);

                if (count($products) === 1 && isset($products[0]) && $products[0] instanceof \product) {
                    $project->setIdProduct($products[0]->id_product);

                    $partnerProduct = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')
                        ->findOneBy(['idPartner' => $project->getIdPartner(), 'idProduct' => $products[0]->id_product]);

                    if (null !== $partnerProduct) {
                        $project
                            ->setIdProduct($partnerProduct->getIdProduct()->getIdProduct())
                            ->setCommissionRateFunds($partnerProduct->getCommissionRateFunds())
                            ->setCommissionRateRepayment($partnerProduct->getCommissionRateRepayment());
                    }
                    $this->entityManager->flush($project);
                }

                if (empty($products) && $addProjectStatus) {
                    $rejectionReason = $this->entityManager
                        ->getRepository('UnilendCoreBusinessBundle:ProjectRejectionReason')
                        ->findBy(['label' => ProjectRejectionReason::PRODUCT_NOT_FOUND]);
                    try {
                        $this->projectStatusManager->rejectProject($project, ProjectsStatus::NOT_ELIGIBLE, $rejectionReason, $userId);
                    } catch (\Exception $exception) {
                        $this->logger->error('Could not reject the project: ' . $project->getIdProject() . '. Error: ' . $exception->getMessage(), [
                            'id_project'       => $project->getIdProject(),
                            'rejection_reason' => ProjectRejectionReason::PRODUCT_NOT_FOUND,
                            'class'            => __CLASS__,
                            'function'         => __FUNCTION__,
                            'file'             => $exception->getFile(),
                            'line'             => $exception->getLine()
                        ]);
                    }
                }

                return count($products);
            } else {
                $this->logger->warning('Cannot find eligible partner product for project ' . $project->getIdProject() . ' id_partner is empty', [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }
        } catch (\Exception $exception) {
            $this->logger->error('An exception occurs when trying to assign the product to the project ' . $project->getIdProject() . '. Errors : ' . $exception->getMessage(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);
        }

        return 0;
    }

    /**
     * @param string $siren
     *
     * @return bool|string
     */
    public function validateSiren(string $siren)
    {
        $siren = preg_replace('/\s/', '', $siren);

        if (1 !== preg_match('/^([0-9]{9}|[0-9]{14})$/', $siren)) {
            return false;
        }

        return substr($siren, 0, 9);
    }

    /**
     * @param string $siret
     *
     * @return bool|string
     */
    public function validateSiret(string $siret)
    {
        $siret = preg_replace('/\s/', '', $siret);

        if (1 !== preg_match('/^[0-9]{14}$/', $siret)) {
            return false;
        }

        return $siret;
    }


    /**
     * @param Projects|\projects $project
     *
     * @return ProjectRejectionReason|null
     */
    public function getMainRejectionReasonMessage($project): string
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('Projects')->find($project->id_project);
        }

        $message                  = $this->translator->trans('project-request_end-page-external-rating-rejection-default-message');
        $lastProjectStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
            ->findOneBy(['idProject' => $project], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC']);

        if (null === $lastProjectStatusHistory) {
            $this->logger->warning('Attempting to get main rejection reason for a project without any status history: ' . $project->getIdProject(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);

            return $message;
        }

        /** @var ProjectStatusHistoryReason[] $rejectionReasons */
        $rejectionReasons = $lastProjectStatusHistory->getRejectionReasons();

        foreach ($rejectionReasons as $reason) {
            if (ProjectRejectionReason::IN_PROCEEDING === $reason->getIdRejectionReason()->getLabel()) {
                $message = $this->translator->trans('project-request_end-page-collective-proceeding-message');
                break;
            } elseif (in_array($reason->getIdRejectionReason()->getLabel(), [ProjectRejectionReason::ENTITY_INACTIVE, ProjectRejectionReason::UNKNOWN_SIREN])) {
                $message = $this->translator->trans('project-request_end-page-no-siren-message');
                break;
            } elseif (in_array($reason->getIdRejectionReason()->getLabel(), [ProjectRejectionReason::NEGATIVE_CAPITAL_STOCK, ProjectRejectionReason::NEGATIVE_RAW_OPERATING_INCOMES, ProjectRejectionReason::NEGATIVE_EQUITY_CAPITAL, ProjectRejectionReason::LOW_TURNOVER])) {
                $message = $this->translator->trans('project-request_end-page-negative-operating-result-message');
                break;
            } elseif (ProjectRejectionReason::PRODUCT_NOT_FOUND === $reason->getIdRejectionReason()->getLabel()) {
                $message = $this->translator->trans('project-request_end-page-product-not-found-message');
                break;
            }
        }

        return $message;
    }

    /**
     * @param Projects|\projects $project
     *
     * @return string
     */
    public function getPartnerMainRejectionReasonMessage($project): string
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('Projects')->find($project->id_project);
        }
        $translation              = 'partner-project-request_not-eligible-reason-no-product';
        $lastProjectStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
            ->findOneBy(['idProject' => $project], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC']);
        $borrowerServiceEmail     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => 'Adresse emprunteur']);

        if (null === $lastProjectStatusHistory) {
            $this->logger->warning('Attempting to get main rejection reason for a partner project without any status history: ' . $project->getIdProject(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);

            return $this->translator->trans($translation, ['%borrowerServiceEmail%' => $borrowerServiceEmail->getValue()]);
        }

        /** @var ProjectStatusHistoryReason[] $rejectionReasons */
        $rejectionReasons = $lastProjectStatusHistory->getRejectionReasons();

        foreach ($rejectionReasons as $rejectionReason) {
            $reasonLabel = $rejectionReason->getIdRejectionReason()->getLabel();
            if (in_array($reasonLabel, [ProjectRejectionReason::ENTITY_INACTIVE, ProjectRejectionReason::UNKNOWN_SIREN])) {
                $translation = 'partner-project-request_not-eligible-reason-unknown-or-inactive-siren';
                break;
            } elseif (ProjectRejectionReason::NO_LEGAL_STATUS === $reasonLabel) {
                $translation = 'partner-project-request_not-eligible-reason-no-legal-personality';
                break;
            } elseif (ProjectRejectionReason::COMPANY_LOCATION === $reasonLabel) {
                $translation = 'partner-project-request_not-eligible-reason-company-location';
                break;
            } elseif (ProjectRejectionReason::IN_PROCEEDING === $reasonLabel) {
                $translation = 'partner-project-request_not-eligible-reason-collective-proceeding';
                break;
            } elseif (in_array($reasonLabel, ProjectRejectionReason::SCORING_REASONS)) {
                $translation = 'partner-project-request_not-eligible-reason-scoring';
                break;
            } elseif (in_array($reasonLabel, ProjectRejectionReason::FINANCIAL_REASONS)) {
                $translation = 'partner-project-request_not-eligible-reason-financial-data';
                break;
            }
        }

        return $this->translator->trans($translation, ['%borrowerServiceEmail%' => $borrowerServiceEmail->getValue()]);
    }

    /**
     * @param int $amount
     *
     * @return int|false
     */
    public function checkRequestedAmount(int $amount)
    {
        $amount = filter_var($amount, FILTER_VALIDATE_INT, ['options' => ['min_range' => $this->projectManager->getMinProjectAmount(), 'max_range' => $this->projectManager->getMaxProjectAmount()]]);

        return $amount;
    }
}
