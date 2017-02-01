<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetList;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummary;

class AltaresManager
{
    const RESOURCE_COMPANY_SCORE     = 'get_score_altares';
    const RESOURCE_BALANCE_SHEET     = 'get_balance_sheet_altares';
    const RESOURCE_COMPANY_IDENTITY  = 'get_company_identity_altares';
    const RESOURCE_FINANCIAL_SUMMARY = 'get_financial_summary_altares';
    const RESOURCE_MANAGEMENT_LINE   = 'get_balance_management_line_altares';

    /** @var string */
    private $login;
    /** @var string */
    private $password;
    /** @var LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var \SoapClient */
    private $identityClient;
    /** @var \SoapClient */
    private $riskClient;
    /** @var Serializer */
    private $serializer;
    /** @var boolean */
    private $monitoring;
    /** @var  ResourceManager */
    private $resourceManager;

    /**
     * AltaresManager constructor.
     * @param string $login
     * @param string $password
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param \SoapClient $identityClient
     * @param \SoapClient $riskClient
     * @param Serializer $serializer
     * @param ResourceManager $resourceManager
     */
    public function __construct($login, $password, LoggerInterface $logger, CallHistoryManager $callHistoryManager, \SoapClient $identityClient, \SoapClient $riskClient, Serializer $serializer, ResourceManager $resourceManager)
    {
        $this->login              = $login;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->identityClient     = $identityClient;
        $this->riskClient         = $riskClient;
        $this->serializer         = $serializer;
        $this->resourceManager    = $resourceManager;
    }

    /**
     * @param boolean $activate
     */
    public function setMonitoring($activate)
    {
        $this->monitoring = $activate;
    }

    /**
     * @param string $siren
     * @return null|CompanyRating
     */
    public function getScore($siren)
    {
        if (null !== $response = $this->riskSoapCall(self::RESOURCE_COMPANY_SCORE, ['siren' => $siren])) {
            return $this->serializer->deserialize(json_encode($response), CompanyRating::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @param int $iSheetsCount
     * @return null|BalanceSheetList
     */
    public function getBalanceSheets($siren, $iSheetsCount = 3)
    {
        if (null !== $response = $this->identitySoapCall(self::RESOURCE_BALANCE_SHEET, ['siren' => $siren, 'nbBilans' => $iSheetsCount])) {
            return $this->serializer->deserialize(json_encode($response), BalanceSheetList::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @return null|CompanyIdentity
     * @throws \Exception
     */
    public function getCompanyIdentity($siren)
    {
        if (null !== $response = $this->identitySoapCall(self::RESOURCE_COMPANY_IDENTITY, ['sirenRna' => $siren])) {
            return $this->serializer->deserialize(json_encode($response), CompanyIdentity::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     * @return null|FinancialSummary[]
     * @throws \Exception
     */
    public function getFinancialSummary($siren, $balanceId)
    {
        if (null !== $response = $this->identitySoapCall(self::RESOURCE_FINANCIAL_SUMMARY, ['siren' => $siren, 'bilanId' => $balanceId])) {
            return $this->serializer->deserialize(json_encode($response->syntheseFinanciereList), 'ArrayCollection<' . FinancialSummary::class . '>', 'json', DeserializationContext::create()->setGroups(['summary']));
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     * @return null|FinancialSummary[]
     * @throws \Exception
     */
    public function getBalanceManagementLine($siren, $balanceId)
    {
        if (null !== $response = $this->identitySoapCall(self::RESOURCE_MANAGEMENT_LINE, ['siren' => $siren, 'bilanId' => $balanceId])) {
            return $this->serializer->deserialize(json_encode($response->SIGList), 'ArrayCollection<' . FinancialSummary::class . '>', 'json', DeserializationContext::create()->setGroups(['management_line']));
        }

        return null;
    }

    /**
     * Make SOAP call to Altares identity WS
     * @param string $resourceLabel
     * @param array $params
     * @return mixed
     */
    private function identitySoapCall($resourceLabel, $params)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $siren      = $params[$this->getSirenKey($wsResource->resource_name)];

        try {
            if (false === $response = $this->callHistoryManager->getStoredResponse($wsResource, $siren)) {
                $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren);
                ini_set("default_socket_timeout", 8);
                $response = $this->identityClient->__soapCall(
                    $wsResource->resource_name,
                    [
                        ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
                    ]
                );
                call_user_func($callable, json_encode($response));
                $this->logger->info('Call to ' . $wsResource->resource_name . '. Response: ' . json_encode($response, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
            } else {
                $response = json_decode($response);
                $this->setMonitoring(false);
            }

            if ($response->return->correct && $response->return->myInfo) {
                if ($this->monitoring) {
                    $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');
                }

                return $response->return->myInfo;
            }
        } catch (\Exception $exception) {
            if ($this->monitoring) {
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down');
            }
            $this->logger->error($exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__] + $params);
        }

        return null;
    }

    /**
     * Make SOAP call to Altares risk WS
     * @param string $resourceLabel
     * @param array $params
     * @return mixed
     */
    private function riskSoapCall($resourceLabel, $params)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $siren      = $params[$this->getSirenKey($wsResource->resource_name)];

        try {
            if (false === $response = $this->callHistoryManager->getStoredResponse($wsResource, $siren)) {
                $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren);
                ini_set("default_socket_timeout", 8);
                $response = $this->riskClient->__soapCall(
                    $wsResource->resource_name,
                    [
                        ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
                    ]
                );
                call_user_func($callable, json_encode($response));
                $this->logger->info('Call to ' . $wsResource->resource_name . '. Response: ' . json_encode($response, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
            } else {
                $response = json_decode($response);
                $this->setMonitoring(false);
            }

            if ($response->return->correct && $response->return->myInfo) {
                if ($this->monitoring) {
                    $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');
                }

                return $response->return->myInfo;
            }
        } catch (\Exception $exception) {
            if ($this->monitoring) {
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down');
            }
            $this->logger->error($exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__] + $params);
        }
        return null;
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
     * @return string
     */
    private function getSirenKey($action)
    {
        switch ($action) {
            case 'getIdentiteAltaN3Entreprise':
                return 'sirenRna';
            default:
                return 'siren';
        }
    }
}
