<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, Companies, CompanyRating, CompanyStatus, Projects, ProjectsStatus, TaxType, Users, WalletType
};
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

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var WalletCreationManager */
    private $walletCreationManager;
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

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     * @param WalletCreationManager  $walletCreationManager
     * @param SourceManager          $sourceManager
     * @param PartnerManager         $partnerManager
     * @param EligibilityManager     $eligibilityManager
     * @param LoggerInterface        $logger
     * @param PartnerProductManager  $partnerProductManager
     * @param CompanyManager         $companyManager
     * @param ProjectStatusManager   $projectStatusManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        WalletCreationManager $walletCreationManager,
        SourceManager $sourceManager,
        PartnerManager $partnerManager,
        EligibilityManager $eligibilityManager,
        LoggerInterface $logger,
        PartnerProductManager $partnerProductManager,
        CompanyManager $companyManager,
        ProjectStatusManager $projectStatusManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->walletCreationManager  = $walletCreationManager;
        $this->sourceManager          = $sourceManager;
        $this->partnerManager         = $partnerManager;
        $this->eligibilityManager     = $eligibilityManager;
        $this->logger                 = $logger;
        $this->partnerProductManager  = $partnerProductManager;
        $this->companyManager         = $companyManager;
        $this->projectStatusManager   = $projectStatusManager;
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

        $fCommission    = ($oFinancial->PMT(round(bcdiv(\projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 4), 2) / 12, $period, - $amount) - $oFinancial->PMT(0, $period, - $amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, - $amount) + $fCommission);

        return $monthlyPayment;
    }

    /**
     * @param array $formData
     * @param Users $user
     *
     * @return \projects
     *
     * @throws \Exception
     */
    public function saveSimulatorRequest($formData, Users $user)
    {
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');
        $anyWhiteSpaces = '/\s/';

        if (empty($formData['email']) || false === filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email', self::EXCEPTION_CODE_INVALID_EMAIL);
        }

        if (false === empty($formData['siren'])) {
            $formData['siren'] = preg_replace($anyWhiteSpaces, '', $formData['siren']);
        }
        if (empty($formData['siren']) || 1 !== preg_match('/^([0-9]{9}|[0-9]{14})$/', $formData['siren'])) {
            throw new \InvalidArgumentException('Invalid SIREN = ' . $formData['siren'], self::EXCEPTION_CODE_INVALID_SIREN);
        }

        if (false === empty($formData['amount'])) {
            $formData['amount'] = preg_replace([$anyWhiteSpaces, '/€/'], '', $formData['amount']);
        }
        if (empty($formData['amount']) || false === filter_var($formData['amount'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid amount = ' . $formData['amount'], self::EXCEPTION_CODE_INVALID_AMOUNT);
        }

        if (empty($formData['duration']) || false === filter_var($formData['duration'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid duration', self::EXCEPTION_CODE_INVALID_DURATION);
        }

        if (empty($formData['reason']) || false === filter_var($formData['reason'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid reason', self::EXCEPTION_CODE_INVALID_REASON);
        }

        $email = $formData['email'];
        if ($this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($email, Clients::STATUS_ONLINE)) {
            $email .= '-' . time();
        }

        $client = new Clients();
        $client
            ->setEmail($email)
            ->setIdLangue('fr')
            ->setStatus(Clients::STATUS_ONLINE)
            ->setSource($this->sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($this->sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($this->sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($this->sourceManager->getSource(SourceManager::ENTRY_SLUG));

        $siren             = substr($formData['siren'], 0, 9);
        $siret             = strlen($formData['siren']) === 14 ? $formData['siren'] : '';

        $company = new Companies();
        $company
            ->setSiren($siren)
            ->setSiret($siret)
            ->setStatusAdresseCorrespondance(1)
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->persist($client);

            $company->setIdClientOwner($client);

            $this->entityManager->persist($company);
            $this->entityManager->flush($company);

            $this->walletCreationManager->createWallet($client, WalletType::BORROWER);

            $statusInBonis = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')
                ->findOneBy(['label' => CompanyStatus::STATUS_IN_BONIS]);
            $this->companyManager->addCompanyStatus($company, $statusInBonis, $user);

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('An error occurred while creating client ', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            throw $exception;
        }

        $project->id_company                           = $company->getIdCompany();
        $project->amount                               = $formData['amount'];
        $project->period                               = $formData['duration'];
        $project->id_borrowing_motive                  = $formData['reason'];
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = ProjectsStatus::INCOMPLETE_REQUEST;
        $project->id_partner                           = $this->partnerManager->getDefaultPartner()->getId();
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::INCOMPLETE_REQUEST, $project);

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
     * @return array
     */
    public function addRejectionProjectStatus(string $motive, $project, int $userId): array
    {
        $status = substr($motive, 0, strlen(ProjectsStatus::UNEXPECTED_RESPONSE)) === ProjectsStatus::UNEXPECTED_RESPONSE
            ? ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION
            : ProjectsStatus::NOT_ELIGIBLE;

        $this->projectStatusManager->addProjectStatus($userId, $status, $project, 0, $motive);

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
                        $project->setIdProduct($partnerProduct->getIdProduct()->getIdProduct());
                        $project->setCommissionRateFunds($partnerProduct->getCommissionRateFunds());
                        $project->setCommissionRateRepayment($partnerProduct->getCommissionRateRepayment());
                    }
                    $this->entityManager->flush($project);
                }

                if (empty($products) && $addProjectStatus) {
                    $this->projectStatusManager->addProjectStatus($userId, ProjectsStatus::NOT_ELIGIBLE, $project, 0, ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND);
                }

                return count($products);
            } else {
                $this->logger->warning(
                    'Cannot find eligible partner product for project ' . $project->getIdProject() . ' id_partner is empty',
                    ['method' => __METHOD__]
                );
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'An exception occurs when trying to assign the product to the project ' . $project->getIdProject() . '. Errors : ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return 0;
    }
}
