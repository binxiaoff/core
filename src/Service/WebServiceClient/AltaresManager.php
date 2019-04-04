<?php

namespace Unilend\Service\WebServiceClient;

use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use SoapClient;
use Unilend\Entity\WsExternalResource;
use Unilend\Entity\External\Altares\{BalanceSheetList, BalanceSheetListDetail, CompanyIdentity, CompanyIdentityDetail, CompanyRating, CompanyRatingDetail, EstablishmentIdentity,
    EstablishmentIdentityDetail, FinancialSummary, FinancialSummaryListDetail, RiskDataMonitoring\NotificationInformation};

class AltaresManager
{
    const RESOURCE_COMPANY_SCORE          = 'get_score_altares';
    const RESOURCE_BALANCE_SHEET          = 'get_balance_sheet_altares';
    const RESOURCE_COMPANY_IDENTITY       = 'get_company_identity_altares';
    const RESOURCE_ESTABLISHMENT_IDENTITY = 'get_establishment_identity_altares';
    const RESOURCE_FINANCIAL_SUMMARY      = 'get_financial_summary_altares';
    const RESOURCE_MANAGEMENT_LINE        = 'get_balance_management_line_altares';

    const RESOURCE_START_MONITORING      = 'start_monitoring_altares';
    const RESOURCE_END_MONITORING        = 'stop_monitoring_altares';
    const RESOURCE_GET_NOTIFICATIONS     = 'get_notification_altares';
    const RESOURCE_SET_NOTIFICATION_READ = 'set_notification_read_altares';

    const EXCEPTION_CODE_INVALID_OR_UNKNOWN_SIREN        = [101, 102, 108, 109, 106];
    const EXCEPTION_CODE_NO_FINANCIAL_DATA               = [118];
    const EXCEPTION_CODE_TECHNICAL_ERROR                 = [-1, 0, 1, 2, 3, 4, 5, 7, 8];
    const EXCEPTION_CODE_ALTARES_DOWN                    = -999;
    const EXCEPTION_CODE_ALTARES_SIREN_ALREADY_MONITORED = 503;
    const EXCEPTION_CODE_ALTARES_SIREN_NOT_MONITORED     = 504;

    /** RiskDataMonitoring Notification Status for WS call*/
    const NOTIFICATION_STATUS_NOT_READ = 2;
    const NOTIFICATION_STATUS_READ     = 1;
    const NOTIFICATION_STATUS_ALL      = 3;

    const DEFAULT_PAGE_NUMBER = 1;

    const CALL_TIMEOUT = 8;

    /** @var string */
    private $login;
    /** @var string */
    private $password;
    /** @var LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var SoapClient */
    private $identityClient;
    /** @var SoapClient */
    private $riskClient;
    /** @var SoapClient */
    private $riskMonitoringClient;
    /** @var SerializerInterface */
    private $serializer;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $saveToCache = true;
    /** @var bool */
    private $readFromCache = true;

    /**
     * @param string              $login
     * @param string              $password
     * @param LoggerInterface     $wsClientLogger
     * @param CallHistoryManager  $callHistoryManager
     * @param SoapClient          $identityClient
     * @param SoapClient          $riskClient
     * @param SoapClient          $riskMonitoringClient
     * @param SerializerInterface $serializer
     * @param ResourceManager     $resourceManager
     */
    public function __construct(
        $login,
        $password,
        LoggerInterface $wsClientLogger,
        CallHistoryManager $callHistoryManager,
        SoapClient $identityClient,
        SoapClient $riskClient,
        SoapClient $riskMonitoringClient,
        SerializerInterface $serializer,
        ResourceManager $resourceManager
    )
    {
        $this->login                = $login;
        $this->password             = $password;
        $this->logger               = $wsClientLogger;
        $this->callHistoryManager   = $callHistoryManager;
        $this->identityClient       = $identityClient;
        $this->riskClient           = $riskClient;
        $this->riskMonitoringClient = $riskMonitoringClient;
        $this->serializer           = $serializer;
        $this->resourceManager      = $resourceManager;
    }

    /**
     * Should be replaced by method parameters instead of class parameters
     *
     * @param bool $saveToCache
     *
     * @return AltaresManager
     */
    public function setSaveToCache($saveToCache)
    {
        $this->saveToCache = $saveToCache;

        return $this;
    }

    /**
     * Should be replaced by method parameters instead of class parameters
     *
     * @param bool $readFromCache
     *
     * @return AltaresManager
     */
    public function setReadFromCache($readFromCache)
    {
        $this->readFromCache = $readFromCache;

        return $this;
    }

    /**
     * @param string $siren
     *
     * @return CompanyRatingDetail
     */
    public function getScore($siren)
    {
        if (null !== ($response = $this->soapCall($this->riskClient, self::RESOURCE_COMPANY_SCORE, ['siren' => $siren]))) {
            /** @var CompanyRating $companyRating */
            $companyRating = $this->serializer->deserialize(json_encode($response->return), CompanyRating::class, 'json');

            return $companyRating->getMyInfo();
        }

        return null;
    }

    /**
     * @param string $siren
     * @param int    $balanceSheetsCount
     *
     * @return BalanceSheetListDetail
     */
    public function getBalanceSheets($siren, $balanceSheetsCount = 3)
    {
        if (null !== ($response = $this->soapCall($this->identityClient, self::RESOURCE_BALANCE_SHEET, ['siren' => $siren, 'nbBilans' => $balanceSheetsCount]))) {
            if (isset($response->return->myInfo->nbBilan) && 1 === $response->return->myInfo->nbBilan) {
                $response->return->myInfo->bilans = [$response->return->myInfo->bilans];
            }
            /** @var BalanceSheetList $balanceSheetList */
            $balanceSheetList = $this->serializer->deserialize(json_encode($response->return), BalanceSheetList::class, 'json');

            return $balanceSheetList->getMyInfo();
        }

        return null;
    }

    /**
     * @param string $siren
     *
     * @return null|CompanyIdentityDetail
     *
     * @throws \Exception
     */
    public function getCompanyIdentity($siren)
    {
        $response = $this->soapCall($this->identityClient, self::RESOURCE_COMPANY_IDENTITY, ['sirenRna' => $siren]);

        if (null === $response) {
            throw new \RuntimeException(self::RESOURCE_COMPANY_IDENTITY . ' resource is down', self::EXCEPTION_CODE_ALTARES_DOWN);
        } else {
            /** @var CompanyIdentity $companyIdentity */
            $companyIdentity = $this->serializer->deserialize(json_encode($response->return), CompanyIdentity::class, 'json');

            return $companyIdentity->getMyInfo();
        }
    }

    /**
     * @param string $siren
     *
     * @return null|EstablishmentIdentityDetail
     *
     * @throws \Exception
     */
    public function getEstablishmentIdentity($siren)
    {
        if (null !== ($response = $this->soapCall($this->identityClient, self::RESOURCE_ESTABLISHMENT_IDENTITY, ['sirenSiret' => $siren]))) {
            /** @var EstablishmentIdentity $establishmentIdentity */
            $establishmentIdentity = $this->serializer->deserialize(json_encode($response->return), EstablishmentIdentity::class, 'json');

            return $establishmentIdentity->getMyInfo();
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     *
     * @return null|FinancialSummaryListDetail
     *
     * @throws \Exception
     */
    public function getFinancialSummary($siren, $balanceId)
    {
        if (null !== ($response = $this->soapCall($this->identityClient, self::RESOURCE_FINANCIAL_SUMMARY, ['siren' => $siren, 'bilanId' => $balanceId]))) {
            /** @var FinancialSummary $financialSummary */
            $financialSummary = $this->serializer->deserialize(json_encode($response->return), FinancialSummary::class, 'json');

            return $financialSummary->getMyInfo();
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     *
     * @return null|FinancialSummaryListDetail
     *
     * @throws \Exception
     */
    public function getBalanceManagementLine($siren, $balanceId)
    {
        if (null !== ($response = $this->soapCall($this->identityClient, self::RESOURCE_MANAGEMENT_LINE, ['siren' => $siren, 'bilanId' => $balanceId]))) {
            /** @var FinancialSummary $financialSummaryListDetail */
            $balanceManagementLine = $this->serializer->deserialize(json_encode($response->return), FinancialSummary::class, 'json');

            return $balanceManagementLine->getMyInfo();
        }

        return null;
    }

    /**
     * @param SoapClient $soapClient
     * @param string     $resourceLabel
     * @param array      $params
     *
     * @return mixed
     */
    private function soapCall(SoapClient $soapClient, $resourceLabel, array $params)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $siren      = $params[$this->getSirenKey($wsResource->getResourceName())];

        try {
            if ($storedResponse = $this->getStoredResponse($wsResource, $siren)) {
                if ($this->isValidResponse($storedResponse)['is_valid']) {
                    return $storedResponse;
                }
            }
            $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->saveToCache);
            ini_set('default_socket_timeout', self::CALL_TIMEOUT);

            $response = $soapClient->__soapCall(
                $wsResource->getResourceName(), [
                ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
            ]);

            $validity = $this->isValidResponse($response, ['class' => __CLASS__, 'resource' => $wsResource->getLabel()] + $params);
            call_user_func($callable, json_encode($response), $validity['status']);

            if ($validity['is_valid']) {
                return $response;
            } else {
                return null;
            }
        } catch (\Exception $exception) {
            if (isset($callable)) {
                call_user_func($callable, isset($response) ? json_encode($response) : null, 'error');
            }
            $this->logger->error($exception->getMessage() . ' - Code ' . $exception->getCode(),
                ['class' => __CLASS__, 'resource' => $wsResource->getLabel()] + $params
            );

            return null;
        }
    }

    /**
     * @return string
     */
    private function getIdentification()
    {
        return $this->login . '|' . $this->password;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    private function getSirenKey($action)
    {
        switch ($action) {
            case 'getIdentiteAltaN3Entreprise':
                return 'sirenRna';
            case 'getIdentiteAltaN3Etablissement':
                return 'sirenSiret';
            default:
                return 'siren';
        }
    }

    /**
     * @param mixed $response
     * @param array $logContext
     *
     * @return array
     */
    private function isValidResponse($response, array $logContext = [])
    {
        if (
            false === isset($response->return)
            || (false === isset($response->return->exception) && false === isset($response->return->myInfo))
            || (isset($response->return->exception, $response->return->myInfo))
        ) {
            if (false === empty($logContext)) {
                $this->logger->error('Altares response could not be handled: "' . json_encode($response) . '"', $logContext);
            }

            return ['status' => 'error', 'is_valid' => false];
        }

        if (isset($response->return->exception)) {
            if (
                in_array($response->return->exception->code, self::EXCEPTION_CODE_INVALID_OR_UNKNOWN_SIREN)
                || in_array($response->return->exception->code, self::EXCEPTION_CODE_NO_FINANCIAL_DATA)
            ) {
                return ['status' => 'valid', 'is_valid' => true];
            } elseif (in_array($response->return->exception->code, self::EXCEPTION_CODE_TECHNICAL_ERROR)) {
                if (false === empty($logContext)) {
                    $this->logger->error('Altares response technical error: "' . $response->return->exception->code . ' : ' . $response->return->exception->description . '"', $logContext);
                }

                return ['status' => 'error', 'is_valid' => false];
            } else {
                if (false === empty($logContext)) {
                    $this->logger->warning('Altares response code not expected: "' . $response->return->exception->code . ' : ' . $response->return->exception->description . '"', $logContext);
                }

                return ['status' => 'warning', 'is_valid' => false];
            }
        } elseif (isset($response->return->myInfo)) {
            return ['status' => 'valid', 'is_valid' => true];
        }
        if (false === empty($logContext)) {
            $this->logger->error('Unexpected Altares response: ' . json_encode($response), $logContext);
        }

        return ['status' => 'error', 'is_valid' => false];
    }

    /**
     * @param WsExternalResource $resource
     * @param string             $siren
     *
     * @return mixed;
     */
    private function getStoredResponse($resource, $siren)
    {
        if (
            $this->readFromCache
            && false !== ($storedResponse = $this->callHistoryManager->getStoredResponse($resource, $siren))
            && null !== ($storedResponse = json_decode($storedResponse))
            && isset($storedResponse->return)
        ) {
            return $storedResponse;
        }

        return false;
    }


    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Exception
     */
    public function startMonitoring(string $siren): bool
    {
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_START_MONITORING);
        $response   = $this->riskMonitoringClient->__soapCall(
            $wsResource->getResourceName(), [
            ['identification' => $this->getIdentification(), 'refClient' => 'sffpme', 'ajoutAlerte' => true, 'operation' => 1, 'siren' => $siren]
        ]);

        if (null !== $response) {
            if (
                $response->return->correct
                || null !== $response->return->exception && self::EXCEPTION_CODE_ALTARES_SIREN_ALREADY_MONITORED == $response->return->exception->code
            ) {
                return true;
            }
            if (null !== $response->return->exception && false === empty($response->return->exception->description)) {
                throw new \Exception('Altares exception: ' . $response->return->exception->description);
            }
        }

        return false;
    }

    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Exception
     */
    public function stopMonitoring(string $siren): bool
    {
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_END_MONITORING);
        $response   = $this->riskMonitoringClient->__soapCall(
            $wsResource->getResourceName(), [
            ['identification' => $this->getIdentification(), 'siren' => $siren]
        ]);

        if (null !== $response) {
            if (
                $response->return->correct
                || null !== $response->return->exception && self::EXCEPTION_CODE_ALTARES_SIREN_NOT_MONITORED == $response->return->exception->code
            ) {
                return true;
            }

            if (null !== $response->return->exception && false === empty($response->return->exception->description)) {
                throw new \Exception('Altares exception: ' . $response->return->exception->description);
            }
        }

        return false;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int       $numberEvents
     * @param int|null  $page
     *
     * @return null|NotificationInformation
     * @throws \Exception
     */
    public function getMonitoringEvents(\DateTime $start, \DateTime $end, int $numberEvents, ?int $page = self::DEFAULT_PAGE_NUMBER): ?NotificationInformation
    {
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_GET_NOTIFICATIONS);
        $response   = $this->riskMonitoringClient->__soapCall(
            $wsResource->getResourceName(), [
            [
                'identification' => $this->getIdentification(),
                'refClient'      => 'sffpme',
                'au'             => $end->format('Y-m-d'),
                'du'             => $start->format('Y-m-d'),
                'etatLu'         => self::NOTIFICATION_STATUS_NOT_READ,
                'page'           => $page,
                'taillePage'     => $numberEvents
            ]
        ]);

        if (null !== $response && isset($response->return)) {
            if ($response->return->correct && false === empty($response->return->myInfo)) {
                if (false === is_array($response->return->myInfo->alerteList)) {
                    $response->return->myInfo->alerteList = [$response->return->myInfo->alerteList];
                }

                foreach ($response->return->myInfo->alerteList as $index => $notification) {
                    if (false === is_array($response->return->myInfo->alerteList[$index]->evenementList)) {
                        $response->return->myInfo->alerteList[$index]->evenementList = [$response->return->myInfo->alerteList[$index]->evenementList];
                    }
                }
                /** @var NotificationInformation $notificationInformation */
                $notificationInformation = $this->serializer->deserialize(json_encode($response->return->myInfo), NotificationInformation::class, 'json');

                return $notificationInformation;
            }

            if (null !== $response->return->exception && false === empty($response->return->exception->description)) {
                throw new \Exception('Altares exception: ' . $response->return->exception->description);
            }
        }

        return null;
    }

    /**
     * @param string $notificationId
     *
     * @return bool
     * @throws \Exception
     */
    public function setNotificationAsRead(string $notificationId): bool
    {
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_SET_NOTIFICATION_READ);
        $response   = $this->riskMonitoringClient->__soapCall(
            $wsResource->getResourceName(), [
            [
                'identification' => $this->getIdentification(),
                'refClient'      => 'sffpme',
                'alerteId'       => $notificationId,
                'bLu'            => true
            ]
        ]);

        if (null !== $response) {
            if ($response->return->correct) {
                return true;
            }

            if (null !== $response->return->exception && false === empty($response->return->exception->description)) {
                throw new \Exception('Altares exception: ' . $response->return->exception->description);
            }
        }

        return false;
    }
}
