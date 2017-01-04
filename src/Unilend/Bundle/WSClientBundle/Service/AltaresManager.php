<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Psr\Log\LoggerInterface;

class AltaresManager
{
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

    /**
     * AltaresManager constructor.
     * @param string $login
     * @param string $password
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param \SoapClient $identityClient
     * @param \SoapClient $riskClient
     */
    public function __construct($login, $password, LoggerInterface $logger, CallHistoryManager $callHistoryManager, \SoapClient $identityClient, \SoapClient $riskClient)
    {
        $this->login              = $login;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->identityClient     = $identityClient;
        $this->riskClient         = $riskClient;
    }

    /**
     * Returns the score and outstanding amounts from a siren
     * @param $siren
     * @return mixed
     */
    public function getScore($siren)
    {
        return $this->riskSoapCall('getScore', ['siren' => $siren]);
    }

    public function getBalanceSheets($siren, $iSheetsCount = 3)
    {
        return $this->identitySoapCall('getDerniersBilans', ['siren' => $siren, 'nbBilans' => $iSheetsCount]);
    }

    /**
     * @param $siren
     * @return mixed
     */
    public function getCompanyIdentity($siren)
    {
        $response = $this->identitySoapCall('getIdentiteAltaN3Entreprise', ['sirenRna' => $siren]);
        return $response;
    }

    /**
     * @param $siren
     * @return mixed
     */
    public function getFinancialSummary($siren)
    {
        return $this->identitySoapCall('getSyntheseFinanciere', ['siren' => $siren]);
    }

    /**
     * @param string $siren
     * @param string $balanceId
     * @return mixed
     */
    public function getBalanceManagementLine($siren, $balanceId)
    {
        return $this->identitySoapCall('getSoldeIntermediaireGestion', ['siren' => $siren, 'bilanId' => $balanceId]);
    }

    /**
     * Make SOAP call to Altares identity WS
     * @param string $action
     * @param array $params
     * @return mixed
     */
    private function identitySoapCall($action, $params)
    {
        $callable = $this->callHistoryManager->addResourceCallHistoryLog('altares', $action, 'POST', $params[$this->getSirenKey($action)]);
        $response = $this->identityClient->__soapCall(
            $action,
            [
                ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
            ]
        );
        call_user_func($callable);
        $this->logger->info('Call to ' . $action . '. Response: ' . json_encode($response, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $params[$this->getSirenKey($action)]]);
        return $response;
    }
    /**
     * Make SOAP call to Altares risk WS
     * @param string $action
     * @param array $params
     * @return mixed
     */
    private function riskSoapCall($action, $params)
    {
        $callable = $this->callHistoryManager->addResourceCallHistoryLog('altares', $action, 'POST', $params[$this->getSirenKey($action)]);
        $response = $this->riskClient->__soapCall(
            $action,
            [
                ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
            ]
        );
        call_user_func($callable);
        $this->logger->info('Call to ' . $action . '. Response: ' . json_encode($response, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $params[$this->getSirenKey($action)]]);
        return $response;
    }

    /**
     * @return string
     */
    private function getIdentification()
    {
        return $this->login . '|' . $this->password;
    }

    /**
     * @param string
     * @return string
     */
    private function getSirenKey($action)
    {
        switch ($action) {
            case 'getCompanyIdentity':
                return 'sirenRna';
            default:
                return 'siren';
        }
    }
}
