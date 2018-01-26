<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyRatingHistory
};
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager as AltaresWsClient;

class AltaresManager
{
    const PROVIDER_NAME = 'altares';

    /** @var EntityManager */
    private $entityManager;
    /** @var AltaresWsClient */
    private $altaresWsManager;
    /** @var CompanyValidator */
    private $companyValidator;
    /** @var ExternalDataManager */
    private $externalDataManager;
    /** @var DataWriter */
    private $dataWriter;
    /** @var MonitoringManger */
    private $monitoringManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager       $entityManager
     * @param AltaresWsClient     $altaresWsManager
     * @param CompanyValidator    $companyValidator
     * @param ExternalDataManager $externalDataManager
     * @param DataWriter          $dataWriter
     * @param MonitoringManger    $monitoringManager
     * @param LoggerInterface     $logger
     */
    public function __construct(
        EntityManager $entityManager,
        AltaresWsClient $altaresWsManager,
        CompanyValidator $companyValidator,
        ExternalDataManager $externalDataManager,
        DataWriter $dataWriter,
        MonitoringManger $monitoringManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager       = $entityManager;
        $this->altaresWsManager    = $altaresWsManager;
        $this->companyValidator    = $companyValidator;
        $this->externalDataManager = $externalDataManager;
        $this->dataWriter          = $dataWriter;
        $this->monitoringManager   = $monitoringManager;
        $this->logger              = $logger;
    }

    /**
     * @param string $siren
     * @param string $altaresCode
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function saveAltaresMonitoringEvent(string $siren, string $altaresCode) : void
    {
        $monitoring         = $this->monitoringManager->getMonitoringForSiren($siren, self::PROVIDER_NAME);
        $monitoredCompanies = $this->monitoringManager->getMonitoredCompanies($siren, self::PROVIDER_NAME);
        $monitoringTypes    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringType')->findBy(['provider' => self::PROVIDER_NAME]);

        $this->refreshDataByEventCode($siren, $altaresCode);

        /** @var Companies $company */
        foreach ($monitoredCompanies as $company) {
            $companyRatingHistory = $this->dataWriter->createCompanyRatingHistory($company);
            $monitoringCallLog    = $this->dataWriter->createMonitoringEvent($monitoring, $companyRatingHistory);

            $this->saveRefreshedDataInCompanyRatingHistory($companyRatingHistory);

            foreach ($monitoringTypes as $type) {
                if (null !== $type->getIdProjectEligibilityRule()) {
                    $result     = $this->companyValidator->checkRule($type->getIdProjectEligibilityRule()->getLabel(), $siren);
                    $assessment = empty($result) ? true : $result[0];
                    $this->dataWriter->saveAssessment($type, $monitoringCallLog, $assessment);

                } else {
                    $this->logger->warning('Altares risk data monitoring type has no corresponding project eligibility rule and is not supported by code', [
                        'file'   => __CLASS__,
                        'line'   => __LINE__,
                        'idType' => $type->getId()
                    ]);
                }
            }
            $this->dataWriter->saveMonitoringEventInProjectMemos($monitoringCallLog, self::PROVIDER_NAME);

        }
        $this->entityManager->flush();
    }

    /**
     * @param CompanyRatingHistory $companyRatingHistory
     *
     * @throws \Exception
     */
    private function saveRefreshedDataInCompanyRatingHistory(CompanyRatingHistory $companyRatingHistory) : void
    {
        $this->externalDataManager->setCompanyRatingHistory($companyRatingHistory);
        $this->externalDataManager->getCompanyIdentity($companyRatingHistory->getIdCompany()->getSiren());
        $this->externalDataManager->getBalanceSheets($companyRatingHistory->getIdCompany()->getSiren());
        $this->externalDataManager->getAltaresScore($companyRatingHistory->getIdCompany()->getSiren());
    }

    /**
     * @param string $siren
     * @param string $altaresCode
     *
     * @throws \Exception
     */
    private function refreshDataByEventCode(string $siren, string $altaresCode) : void
    {
        $this->altaresWsManager->setReadFromCache(false);

        switch ($altaresCode) {
            case 'ALTA_ACT':
            case 'ALTA_ADR':
            case 'ALTA_CAP':
            case 'ALTA_EIRL':
            case 'ALTA_ETA':
            case 'ALTA_FJ':
            case 'ALTA_RS':
                $this->altaresWsManager->getCompanyIdentity($siren);
                break;
            case 'ALTA_SCO':
                $this->altaresWsManager->getScore($siren);
                break;
            case 'ALTA_BIL':
                $this->altaresWsManager->getBalanceSheets($siren);
                break;
            default:
                //TODO don't know what for instance
                break;
        }
        $this->altaresWsManager->setReadFromCache(true);
    }

    /**
     * @param string $siren
     *
     * @return bool
     */
    public function activateMonitoring(string $siren) : bool
    {
        return $this->altaresWsManager->startMonitoring($siren);
    }

    /**
     * @param string $siren
     *
     * @return bool
     */
    public function stopMonitoring(string $siren) : bool
    {
        return $this->altaresWsManager->stopMonitoring($siren);
    }


    public function setEventAsRead(String $eventId)
    {

    }
}
