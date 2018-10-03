<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Companies, CompanyRatingHistory};
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\RiskDataMonitoring\{EventDetail, Notification, NotificationInformation};
use Unilend\Bundle\WSClientBundle\Service\AltaresManager as AltaresWsClient;

class AltaresManager
{
    const PROVIDER_NAME                     = 'altares';
    const EVENT_CODES_IMPACTING_ELIGIBILITY = [
        'ALTA_ACT',
        'ALTA_ADR',
        'ALTA_CAP',
        'ALTA_EIRL',
        'ALTA_ETA',
        'ALTA_FJ',
        'ALTA_RS',
        'ALTA_SCO',
        'ALTA_BIL'
    ];

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var AltaresWsClient */
    private $altaresWsManager;
    /** @var CompanyValidator */
    private $companyValidator;
    /** @var ExternalDataManager */
    private $externalDataManager;
    /** @var DataWriter */
    private $dataWriter;
    /** @var MonitoringManager */
    private $monitoringManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AltaresWsClient        $altaresWsManager
     * @param CompanyValidator       $companyValidator
     * @param ExternalDataManager    $externalDataManager
     * @param DataWriter             $dataWriter
     * @param MonitoringManager      $monitoringManager
     * @param LoggerInterface        $wsClientLogger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AltaresWsClient $altaresWsManager,
        CompanyValidator $companyValidator,
        ExternalDataManager $externalDataManager,
        DataWriter $dataWriter,
        MonitoringManager $monitoringManager,
        LoggerInterface $wsClientLogger
    )
    {
        $this->entityManager       = $entityManager;
        $this->altaresWsManager    = $altaresWsManager;
        $this->companyValidator    = $companyValidator;
        $this->externalDataManager = $externalDataManager;
        $this->dataWriter          = $dataWriter;
        $this->monitoringManager   = $monitoringManager;
        $this->logger              = $wsClientLogger;
    }

    /**
     * @param string $siren
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    private function saveMonitoringEvent(string $siren): void
    {
        $this->entityManager->beginTransaction();
        try {
            if (null === $monitoring = $this->monitoringManager->getMonitoringForSiren($siren, self::PROVIDER_NAME)) {
                $monitoring = $this->dataWriter->startMonitoringPeriod($siren, self::PROVIDER_NAME);
            }

            $monitoredCompanies = $this->monitoringManager->getMonitoredCompanies($siren, self::PROVIDER_NAME);
            $monitoringTypes    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringType')->findBy(['provider' => self::PROVIDER_NAME]);

            $this->refreshData($siren);

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
                            'class'  => __CLASS__,
                            'line'   => __LINE__,
                            'idType' => $type->getId()
                        ]);
                    }
                }
                $this->dataWriter->saveMonitoringEventInProjectMemos($monitoringCallLog, self::PROVIDER_NAME);
            }
            $this->entityManager->commit();

        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('An error occurred while saving Altares monitoring event: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'exceptionFile' => $exception->getFile(),
                'exceptionLine' => $exception->getLine()
            ]);
            throw $exception;
        }
    }

    /**
     * @param CompanyRatingHistory $companyRatingHistory
     *
     * @throws \Exception
     */
    private function saveRefreshedDataInCompanyRatingHistory(CompanyRatingHistory $companyRatingHistory): void
    {
        $this->externalDataManager->setCompanyRatingHistory($companyRatingHistory);
        $this->externalDataManager->getCompanyIdentity($companyRatingHistory->getIdCompany()->getSiren());
        $this->externalDataManager->getBalanceSheets($companyRatingHistory->getIdCompany()->getSiren());
        $this->externalDataManager->getAltaresScore($companyRatingHistory->getIdCompany()->getSiren());
    }

    /**
     * @param string $siren
     *
     * @throws \Exception
     */
    private function refreshData(string $siren): void
    {
        $this->altaresWsManager->setReadFromCache(false);

        $this->altaresWsManager->getCompanyIdentity($siren);
        $this->altaresWsManager->getScore($siren);
        $this->altaresWsManager->getBalanceSheets($siren);

        $this->altaresWsManager->setReadFromCache(true);
    }

    /**
     * @param string $siren
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function activateMonitoring(string $siren): void
    {
        if ($this->altaresWsManager->startMonitoring($siren)) {
            $this->dataWriter->startMonitoringPeriod($siren, self::PROVIDER_NAME);
        }
    }

    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Exception
     */
    public function stopMonitoring(string $siren): bool
    {
        return $this->altaresWsManager->stopMonitoring($siren);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function saveMonitoringEvents(): void
    {
        $lastEvent               = $this->monitoringManager->getLastMonitoringEventDate(self::PROVIDER_NAME);
        $now                     = new \DateTime('NOW');
        $numberOfNotReadEvents   = $this->getNumberOfNotReadNotifications($lastEvent, $now);

        if (0 < $numberOfNotReadEvents) {
            $notificationInformation = $this->altaresWsManager->getMonitoringEvents($lastEvent, $now, $numberOfNotReadEvents);

            $eventAffectsEligibility = false;
            /** @var Notification $notification */
            foreach ($notificationInformation->getNotificationList() as $notification) {
                /** @var EventDetail $event */
                foreach ($notification->getEventList() as $event) {
                    if (in_array($event->getEventCode(), self::EVENT_CODES_IMPACTING_ELIGIBILITY)) {
                        $eventAffectsEligibility = true;
                    }
                }

                if ($eventAffectsEligibility) {
                    $this->saveMonitoringEvent($notification->getSiren());
                    $this->setNotificationAsRead($notification);
                }
            }
        }
    }

    /**
     * @param Notification $notification
     */
    public function setNotificationAsRead(Notification $notification): void
    {
        try {
            $this->altaresWsManager->setNotificationAsRead($notification->getId());
        } catch (\Exception $exception) {
            $this->logger->warning('Altares notification status could not be set to "read". NotificationId: ' . $notification->getId() . ', Exception: ' . $exception->getMessage(), [
                'class'          => __CLASS__,
                'function'       => __FUNCTION__,
                'idNotification' => $notification->getId(),
                'siren'          => $notification->getSiren()
            ]);
        }
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return mixed
     * @throws \Exception
     */
    public function getNumberOfNotReadNotifications(\DateTime $start, \DateTime $end)
    {
        /** @var NotificationInformation $notificationInformation */
        $notificationInformation = $this->altaresWsManager->getMonitoringEvents($start, $end, 1);

        return $notificationInformation->getCountNotReadNotificationsSelection();
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getGlobalNumberOfNotReadEvents()
    {
        $start                   = new \DateTime('First fo September 2013');
        $end                     = new \DateTime('NOW');
        $notificationInformation = $this->altaresWsManager->getMonitoringEvents($start, $end, 1);

        return $notificationInformation->getCountNotReadNotificationsGlobal();
    }

    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Exception
     */
    public function sirenExist(string $siren): bool
    {
        $identity = $this->altaresWsManager->getCompanyIdentity($siren);

        return null !== $identity;
    }
}
