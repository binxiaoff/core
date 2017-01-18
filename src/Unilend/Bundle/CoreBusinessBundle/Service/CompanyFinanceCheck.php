<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetList;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummary;
use Unilend\Bundle\WSClientBundle\Entity\Codinf\PaymentIncident;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\CodinfManager;
use Unilend\Bundle\WSClientBundle\Service\InfogreffeManager;

class CompanyFinanceCheck
{
    const UNEXPECTED_RESPONSE = 'unexpected_response';

    /** @var  EntityManager */
    private $entityManager;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;
    /** @var  LoggerInterface */
    private $logger;
    /** @var  AltaresManager */
    private $wsAltares;
    /** @var CodinfManager */
    private $wsCodinf;
    /** @var InfogreffeManager $wsInfogreffe */
    private $wsInfogreffe;

    /**
     * CompanyFinanceCheck constructor.
     * @param EntityManager $entityManager
     * @param CompanyBalanceSheetManager $companyBalanceSheetManager
     * @param ProjectManager $projectManager
     * @param CacheItemPoolInterface $cacheItemPool
     * @param LoggerInterface $logger
     * @param AltaresManager $wsAltares
     * @param CodinfManager $wsCodinf
     * @param InfogreffeManager $wsInfogreffe
     */
    public function __construct(EntityManager $entityManager, CompanyBalanceSheetManager $companyBalanceSheetManager, ProjectManager $projectManager, CacheItemPoolInterface $cacheItemPool, LoggerInterface $logger, AltaresManager $wsAltares, CodinfManager $wsCodinf, InfogreffeManager $wsInfogreffe)
    {
        $this->entityManager              = $entityManager;
        $this->companyBalanceSheetManager = $companyBalanceSheetManager;
        $this->projectManager             = $projectManager;
        $this->cacheItemPool              = $cacheItemPool;
        $this->logger                     = $logger;
        $this->wsAltares                  = $wsAltares;
        $this->wsCodinf                   = $wsCodinf;
        $this->wsInfogreffe               = $wsInfogreffe;
    }

    /**
     * Check if siren exists in Altares System, if company has collective procedures and if company is active
     * @param \companies $company
     * @param string $rejectionReason
     * @return bool
     */
    public function isCompanySafe(\companies &$company, &$rejectionReason)
    {
        try {
            if (null !== $companyData = $this->wsAltares->getCompanyIdentity($company->siren)) {
                $company->name          = $companyData->getCorporateName();
                $company->forme         = $companyData->getCompanyForm();
                $company->capital       = $companyData->getCapital();
                $company->code_naf      = $companyData->getNAFCode();
                $company->libelle_naf   = $companyData->getNAFLabel();
                $company->adresse1      = $companyData->getAddress();
                $company->city          = $companyData->getCity();
                $company->zip           = $companyData->getPostCode();
                $company->siret         = $companyData->getSiret();
                $company->date_creation = $companyData->getCreationDate()->format('Y-m-d');
                $company->rcs           = $companyData->getRcs();

                $company->update();

                if (true === $companyData->getCollectiveProcedure()) {
                    $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_PROCEEDING;

                    return false;
                }

                if (true === in_array($companyData->getCompanyStatus(), [7, 9])) {
                    $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_INACTIVE;

                    return false;
                }

                return true;
            }
            $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_UNKNOWN_SIREN;
        } catch (\Exception $exception) {
            $rejectionReason = self::UNEXPECTED_RESPONSE;
            $this->logger->error('Could not get company identity: AltaresManager::getCompanyIdentity(' . $company->siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $company->siren]);
        }

        return false;
    }

    /**
     * @param string $siren
     * @return null|BalanceSheetList
     */
    public function getBalanceSheets($siren)
    {
        try {
            return $this->wsAltares->getBalanceSheets($siren);
        } catch (\Exception $exception) {
            $this->logger->error('Could not get balance sheets: AltaresManager::getBalanceSheets(' . $siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
        }

        return null;
    }

    /**
     * Check if there are more than two incidents or if there is at least one incident of type {2, 3, 4, 5, 6} in the last 12 past months
     * @param string $siren
     * @param string $rejectionReason
     * @return bool
     */
    public function hasCodinfPaymentIncident($siren, &$rejectionReason)
    {
        $nonAllowedIncident = [2, 3, 4, 5, 6];
        try {
            $startDate = (new \DateTime())->sub(new \DateInterval('P1Y'));
            $endDate   = new \DateTime();

            if (null !== $incidentList = $this->wsCodinf->getIncidentList($siren, $startDate, $endDate)) {
                /** @var PaymentIncident[] $incidents */
                $incidents = $incidentList->getIncidentList();

                if (count($incidents) > 2) {
                    $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT;

                    return true;
                }

                foreach ($incidents as $incident) {
                    if (true === in_array($incident->getType(), $nonAllowedIncident) && 12 >= $this->numberOfMonthsAgo($incident->getDate())) {
                        $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT;

                        return true;
                    }
                }
                return false;
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get incident list: CodinfManager::getIncidentList(' . $siren . ') for 1 year. Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
        }
        $rejectionReason = self::UNEXPECTED_RESPONSE;

        return true;
    }

    /**
     * @param BalanceSheetList $balanceSheetList
     * @param string $siren
     * @param $rejectionReason
     * @return bool
     */
    public function hasNegativeCapitalStock(BalanceSheetList $balanceSheetList, $siren, &$rejectionReason)
    {
        $lastBalanceSheet = $balanceSheetList->getLastBalanceSheet();
        try {
            if (null !== $financialSummary = $this->wsAltares->getFinancialSummary($siren, $lastBalanceSheet->getBalanceSheetId())) {
                if (null !== $capitalStockPost = $this->getSummaryFinancialPost($financialSummary, 'posteSF_FPRO')) {
                    if ($capitalStockPost->getAmountY() < 0) {
                        $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK;

                        return true;
                    }

                    return false;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get balance sheets: AltaresManager::getBalanceSheets(' . $siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
        }
        $rejectionReason = self::UNEXPECTED_RESPONSE;

        return true;
    }

    /**
     * @param BalanceSheetList $balanceSheetList
     * @param string $siren
     * @param $rejectionReason
     * @return bool
     */
    public function hasNegativeRawOperatingIncomes(BalanceSheetList $balanceSheetList, $siren, &$rejectionReason)
    {
        $lastBalanceSheet = $balanceSheetList->getLastBalanceSheet();
        try {
            if (null !== $balanceManagementLine = $this->wsAltares->getBalanceManagementLine($siren, $lastBalanceSheet->getBalanceSheetId())) {
                if (null !== $rawOperatingIncomePost = $this->getManagementLineFinancialPost($balanceManagementLine, 'posteSIG_EBE')) {
                    if ($rawOperatingIncomePost->getAmountY() < 0) {
                        $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES;

                        return true;
                    }
                    return false;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get balance management line: AltaresManager::getBalanceManagementLine(' . $siren . ', ' . $lastBalanceSheet->getBalanceSheetId() . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
        }
        $rejectionReason = self::UNEXPECTED_RESPONSE;

        return true;
    }

    /**
     * @param string $siren
     * @param $rejectionReason
     * @return bool
     */
    public function hasInfogreffePrivileges($siren, &$rejectionReason)
    {
        $logContext   = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];
        try {
            $privileges = $this->wsInfogreffe->getIndebtedness($siren);

            if (is_array($privileges)) {
                switch ($privileges['code']) {
                    case '013':
                        return false;
                    default:
                        $rejectionReason = $privileges['message'];
                        $this->logger->warning('InfogreffeManager::getIndebtedness(' . $siren .') response: ' . json_encode($privileges), $logContext);

                        return true;
                }
            }

            if (null !== $privileges) {
                $subscription3 = $privileges->getSubscription3();

                if (count($subscription3) > 0) {
                    foreach ($subscription3 as $item) {
                        if (true === $item->getValid()) {
                            $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES;

                            return true;
                        }
                    }
                }
                $subscription4 = $privileges->getSubscription4();

                if (count($subscription4) > 0) {
                    foreach ($subscription4 as $item) {
                        if (true === $item->getValid()) {
                            $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES;

                            return true;
                        }
                    }
                }

                return false;
            }
        } catch (\Exception $exception) {
            $this->logger->warning('Could not get infogreffe privileges: InfogreffeManager::getIndebtedness(' . $siren .'). Message: ' . $exception->getMessage(), $logContext);
        }
        $rejectionReason = self::UNEXPECTED_RESPONSE;

        return true;
    }

    /**
     * @param \DateTime $date
     * @return int
     */
    private function numberOfMonthsAgo(\DateTime $date)
    {
        $diff = (new \DateTime())->diff($date);

        return (int) $diff->format('%y') * 12 + (int) $diff->format('%m');
    }

    /**
     * @param $postList FinancialSummary[]
     * @param string $postType
     * @return null|FinancialSummary
     */
    private function getSummaryFinancialPost(array $postList, $postType)
    {
        foreach ($postList as $post) {
            if ($post->getPost() === $postType) {
                return $post;
            }
        }

        return null;
    }

    /**
     * @param $postList FinancialSummary[]
     * @param string $postType
     * @return null|FinancialSummary
     */
    private function getManagementLineFinancialPost(array $postList, $postType)
    {
        foreach ($postList as $post) {
            if ($post->getKeyPost() === $postType) {
                return $post;
            }
        }

        return null;
    }
}
