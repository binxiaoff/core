<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetListDetail;

class ProjectRequestManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var WalletCreationManager */
    private $walletCreationManager;
    /** @var SourceManager */
    private $sourceManager;
    /** @var PartnerManager */
    private $partnerManager;
    /** @var CompanyFinanceCheck */
    private $companyFinanceCheck;
    /** @var CompanyScoringCheck */
    private $companyScoringCheck;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerSimulator     $entityManagerSimulator
     * @param EntityManager              $entityManager
     * @param ProjectManager             $projectManager
     * @param WalletCreationManager      $walletCreationManager
     * @param SourceManager              $sourceManager
     * @param PartnerManager             $partnerManager
     * @param CompanyFinanceCheck        $companyFinanceCheck
     * @param CompanyScoringCheck        $companyScoringCheck
     * @param CompanyBalanceSheetManager $companyBalanceSheetManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ProjectManager $projectManager,
        WalletCreationManager $walletCreationManager,
        SourceManager $sourceManager,
        PartnerManager $partnerManager,
        CompanyFinanceCheck $companyFinanceCheck,
        CompanyScoringCheck $companyScoringCheck,
        CompanyBalanceSheetManager $companyBalanceSheetManager,
        LoggerInterface $logger
    )
    {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->projectManager             = $projectManager;
        $this->walletCreationManager      = $walletCreationManager;
        $this->sourceManager              = $sourceManager;
        $this->partnerManager             = $partnerManager;
        $this->companyFinanceCheck        = $companyFinanceCheck;
        $this->companyScoringCheck        = $companyScoringCheck;
        $this->companyBalanceSheetManager = $companyBalanceSheetManager;
        $this->logger                     = $logger;
    }

    public function getMonthlyRateEstimate()
    {
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');

        return round($projects->getGlobalAverageRateOfFundedProjects(50), 1);
    }

    public function getMonthlyPaymentEstimate($amount, $period, $estimatedRate)
    {
        /** @var \PHPExcel_Calculation_Financial $oFinancial */
        $oFinancial = new \PHPExcel_Calculation_Financial();

        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $fVATRate = $taxType->rate / 100;

        $fCommission    = ($oFinancial->PMT(round(bcdiv(\projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 4), 2) / 12, $period, - $amount) - $oFinancial->PMT(0, $period, - $amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, - $amount) + $fCommission);

        return $monthlyPayment;
    }

    /**
     * @param array $formData
     *
     * @return \projects
     * @throws \Exception
     */
    public function saveSimulatorRequest($formData)
    {
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');

        if (empty($formData['email']) || false === filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        if (false === empty($formData['siren'])) {
            $formData['siren'] = str_replace(' ', '', $formData['siren']);
        }
        if (empty($formData['siren']) || 1 !== preg_match('/^([0-9]{9}|[0-9]{14})$/', $formData['siren'])) {
            throw new \InvalidArgumentException('Invalid SIREN = ' . $formData['siren']);
        }
        if (false === empty($formData['amount'])) {
            $formData['amount'] = str_replace([' ', '€'], '', $formData['amount']);
        }
        if (empty($formData['amount']) || false === filter_var($formData['amount'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid amount = ' . $formData['amount']);
        }
        if (empty($formData['duration']) || false === filter_var($formData['duration'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid duration');
        }
        if (empty($formData['reason']) || false === filter_var($formData['reason'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid reason');
        }

        $email = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($formData['email']) ? $formData['email'] . '-' . time() : $formData['email'];

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
        $company->setSiren($siren)
            ->setSiret($siret)
            ->setStatusAdresseCorrespondance(1)
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->persist($client);
            $clientAddress = new ClientsAdresses();
            $clientAddress->setIdClient($client);
            $this->entityManager->persist($clientAddress);
            $this->entityManager->flush($clientAddress);
            $company->setIdClientOwner($client->getIdClient());
            $this->entityManager->persist($company);
            $this->entityManager->flush($company);
            $this->walletCreationManager->createWallet($client, WalletType::BORROWER);
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
        $project->status                               = \projects_status::INCOMPLETE_REQUEST;
        $project->id_partner                           = $this->partnerManager->getDefaultPartner()->getId();
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $this->projectManager->addProjectStatus(Users::USER_ID_FRONT, \projects_status::INCOMPLETE_REQUEST, $project);

        return $project;
    }

    /**
     * @param \companies                  $company
     * @param int                         $userId
     * @param null|BalanceSheetListDetail $balanceSheetList
     * @return null|string
     */
    public function checkCompanyRisk(\companies &$company, $userId, BalanceSheetListDetail &$balanceSheetList = null)
    {
        /** @var \company_rating $companyRating */
        $companyRating                  = $this->entityManagerSimulator->getRepository('company_rating');
        $companyRatingHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $lastCompanyRatingHistory       = $companyRatingHistoryRepository->findOneBy(
            ['idCompany' => $company->id_company],
            ['added' => 'DESC']
        );

        /** @var \company_rating_history $companyRatingHistory */
        $companyRatingHistory             = $this->entityManagerSimulator->getRepository('company_rating_history');
        $companyRatingHistory->id_company = $company->id_company;
        $companyRatingHistory->id_user    = $userId;
        $companyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $companyRatingHistory->create();

        if (null !== $lastCompanyRatingHistory) {
            foreach ($companyRating->getHistoryRatingsByType($lastCompanyRatingHistory->getIdCompanyRatingHistory()) as $rating => $value) {
                if (false === in_array($rating, \company_rating::$automaticRatingTypes)) {
                    $companyRating->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
                    $companyRating->type                      = $rating;
                    $companyRating->value                     = $value['value'];
                    $companyRating->create();
                }
            }
        }

        $riskCheck = $this->checkRisk($company, $balanceSheetList, $companyRatingHistory, $companyRating);

        if (null !== $balanceSheetList) {
            $this->companyBalanceSheetManager->setCompanyBalance($company, $balanceSheetList, $project);
        }

        return $riskCheck;
    }

    /**
     * @param \projects $project
     * @param int       $userId
     *
     * @return null|array
     */
    public function checkProjectRisk(\projects $project, $userId)
    {
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        $company->get($project->id_company);

        $balanceSheetList = null;
        $riskCheck        = $this->checkCompanyRisk($company, $userId, $balanceSheetList);

        $companyRatingHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $lastCompanyRatingHistory       = $companyRatingHistoryRepository->findOneBy(
            ['idCompany' => $company->id_company],
            ['added' => 'DESC']
        );

        $project->balance_count             = null === $company->date_creation ? 0 : \DateTime::createFromFormat('Y-m-d', $company->date_creation)->diff(new \DateTime())->y;
        $project->id_company_rating_history = $lastCompanyRatingHistory->getIdCompanyRatingHistory();
        $project->update();

        if (null !== $balanceSheetList) {
            $this->companyBalanceSheetManager->setCompanyBalance($company, $balanceSheetList, $project);
        }

        if (null !== $riskCheck) {
            return $this->addRejectionProjectStatus($riskCheck, $project, $userId);
        }

        return null;
    }

    /**
     * @param \companies                   $company
     * @param null|BalanceSheetListDetail  $balanceSheetList
     * @param null|\company_rating_history $companyRatingHistory
     * @param null|\company_rating         $companyRating
     * @return null|string
     */
    public function checkRisk(\companies &$company, &$balanceSheetList = null, $companyRatingHistory = null, $companyRating = null)
    {
        if (false === $this->companyFinanceCheck->isCompanySafe($company, $rejectionReason)) {
            return $rejectionReason;
        }

        if ($company->code_naf === Companies::NAF_CODE_NO_ACTIVITY) {
            $altaresScore = $this->companyScoringCheck->getAltaresScore($company->siren);

            if (
                true === $this->companyScoringCheck->isAltaresScoreLow($altaresScore, $rejectionReason, $companyRatingHistory, $companyRating)
                || true === $this->companyScoringCheck->isInfolegaleScoreLow($company->siren, $rejectionReason, $companyRatingHistory, $companyRating)
            ) {
                return $rejectionReason;
            }
        } else {
            if (true === $this->companyFinanceCheck->hasCodinfPaymentIncident($company->siren, $rejectionReason)) {
                return $rejectionReason;
            }

            $altaresScore = $this->companyScoringCheck->getAltaresScore($company->siren);

            if (true === $this->companyScoringCheck->isAltaresScoreLow($altaresScore, $rejectionReason, $companyRatingHistory, $companyRating)) {
                return $rejectionReason;
            }

            $balanceSheetList = $this->companyFinanceCheck->getBalanceSheets($company->siren);

            if (null !== $balanceSheetList && (new \DateTime())->diff($balanceSheetList->getLastBalanceSheet()->getCloseDate())->days <= \company_balance::MAX_COMPANY_BALANCE_DATE) {
                if (
                    true === $this->companyFinanceCheck->hasNegativeCapitalStock($balanceSheetList, $company->siren, $rejectionReason)
                    || true === $this->companyFinanceCheck->hasNegativeRawOperatingIncomes($balanceSheetList, $company->siren, $rejectionReason)
                ) {
                    return $rejectionReason;
                }
            }

            if (
                false === $this->companyScoringCheck->isXerfiUnilendOk($company->code_naf, $rejectionReason, $companyRatingHistory, $companyRating)
                || false === $this->companyScoringCheck->combineAltaresScoreAndUnilendXerfi($altaresScore, $company->code_naf, $rejectionReason)
                || false === $this->companyScoringCheck->combineEulerTrafficLightXerfiAltaresScore($altaresScore, $company, $rejectionReason, $companyRatingHistory, $companyRating)
                || true === $this->companyScoringCheck->isInfolegaleScoreLow($company->siren, $rejectionReason, $companyRatingHistory, $companyRating)
                || false === $this->companyScoringCheck->combineEulerGradeUnilendXerfiAltaresScore($altaresScore, $company, $rejectionReason, $companyRatingHistory, $companyRating)
            ) {
                return $rejectionReason;
            }
        }

        return null;
    }

    /**
     * @param string    $motive
     * @param \projects $project
     * @param int       $userId
     * @return array
     */
    public function addRejectionProjectStatus($motive, &$project, $userId)
    {
        $status = substr($motive, 0, strlen(\projects_status::UNEXPECTED_RESPONSE)) === \projects_status::UNEXPECTED_RESPONSE
            ? \projects_status::IMPOSSIBLE_AUTO_EVALUATION
            : \projects_status::NOT_ELIGIBLE;

        $this->projectManager->addProjectStatus($userId, $status, $project, 0, $motive);

        return ['motive' => $motive, 'status' => $status];
    }
}
