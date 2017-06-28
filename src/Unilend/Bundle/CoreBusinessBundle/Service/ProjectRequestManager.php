<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
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
    /** @var ProjectManager */
    private $projectManager;
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

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     * @param ProjectManager         $projectManager
     * @param WalletCreationManager  $walletCreationManager
     * @param SourceManager          $sourceManager
     * @param PartnerManager         $partnerManager
     * @param EligibilityManager     $eligibilityManager
     * @param LoggerInterface        $logger
     * @param PartnerProductManager  $partnerProductManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ProjectManager $projectManager,
        WalletCreationManager $walletCreationManager,
        SourceManager $sourceManager,
        PartnerManager $partnerManager,
        EligibilityManager $eligibilityManager,
        LoggerInterface $logger,
        PartnerProductManager $partnerProductManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->projectManager         = $projectManager;
        $this->walletCreationManager  = $walletCreationManager;
        $this->sourceManager          = $sourceManager;
        $this->partnerManager         = $partnerManager;
        $this->eligibilityManager     = $eligibilityManager;
        $this->logger                 = $logger;
        $this->partnerProductManager  = $partnerProductManager;
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
     *
     * @return \projects
     *
     * @throws \Exception
     */
    public function saveSimulatorRequest($formData)
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
        $project->status                               = ProjectsStatus::INCOMPLETE_REQUEST;
        $project->id_partner                           = $this->partnerManager->getDefaultPartner()->getId();
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $this->projectManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::INCOMPLETE_REQUEST, $project);

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
     * @param \projects $projectData
     * @param int       $userId
     *
     * @return null|array
     */
    public function checkProjectRisk(\projects $projectData, $userId)
    {
        $project     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectData->id_project);
        $company     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($projectData->id_company);
        $eligibility = $this->checkCompanyRisk($company, $userId, $project);

        $companyRatingHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $lastCompanyRatingHistory       = $companyRatingHistoryRepository->findOneBy(
            ['idCompany' => $company->getIdCompany()],
            ['added' => 'DESC']
        );

        $lastBalance = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompaniesBilans')
            ->findBy(['idCompany' => $company->getIdCompany()], ['clotureExerciceFiscal' => 'DESC'], 1);

        if (false === empty($lastBalance)) {
            $projectData->id_dernier_bilan = $lastBalance[0]->getIdBilan();
        }

        $projectData->balance_count             = null === $company->getDateCreation() ? 0 : $company->getDateCreation()->diff(new \DateTime())->y;
        $projectData->id_company_rating_history = $lastCompanyRatingHistory->getIdCompanyRatingHistory();
        $projectData->update();

        if (is_array($eligibility) && false === empty($eligibility)) {
            return $this->addRejectionProjectStatus($eligibility[0], $projectData, $userId);
        }

        return null;
    }

    /**
     * @param string    $motive
     * @param \projects $project
     * @param int       $userId
     *
     * @return array
     */
    public function addRejectionProjectStatus($motive, $project, $userId)
    {
        $status = substr($motive, 0, strlen(ProjectsStatus::UNEXPECTED_RESPONSE)) === ProjectsStatus::UNEXPECTED_RESPONSE
            ? ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION
            : ProjectsStatus::NOT_ELIGIBLE;

        $this->projectManager->addProjectStatus($userId, $status, $project, 0, $motive);

        return ['motive' => $motive, 'status' => $status];
    }

    /**
     * @param \projects $project
     * @param null|int  $userId
     * @param boolean   $addProjectStatus
     *
     * @return int
     */
    public function assignEligiblePartnerProduct(\projects $project, $userId = null, $addProjectStatus = false)
    {
        try {
            if (false === empty($project->id_partner)) {
                $products = $this->partnerProductManager->findEligibleProducts($project);

                if (count($products) === 1 && isset($products[0]) && $products[0] instanceof \product) {
                    $project->id_product = $products[0]->id_product;

                    $partnerProduct = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')
                        ->findOneBy(['idPartner' => $project->id_partner, 'idProduct' => $products[0]->id_product]);

                    if (null !== $partnerProduct) {
                        $project->id_product                = $partnerProduct->getIdProduct()->getIdProduct();
                        $project->commission_rate_funds     = $partnerProduct->getCommissionRateFunds();
                        $project->commission_rate_repayment = $partnerProduct->getCommissionRateRepayment();
                        $project->update();
                    }
                }

                if (empty($products) && $addProjectStatus) {
                    $this->projectManager->addProjectStatus($userId, ProjectsStatus::NOT_ELIGIBLE, $project, 0, ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND);
                }

                return count($products);
            } else {
                $this->logger->warning('Cannot find eligible partner product for project ' . $project->id_project . ' id_partner is empty');
            }
        } catch (\Exception $exception) {
            $this->logger->error('An exception occurs when trying to assign the product to the project ' . $project->id_project . '. Errors : ' . $exception->getMessage());
        }

        return 0;
    }
}
