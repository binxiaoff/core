<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummary;

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
    /** @var Serializer */
    private $serializer;

    /**
     * AltaresManager constructor.
     * @param string $login
     * @param string $password
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param \SoapClient $identityClient
     * @param \SoapClient $riskClient
     * @param Serializer $serializer
     */
    public function __construct($login, $password, LoggerInterface $logger, CallHistoryManager $callHistoryManager, \SoapClient $identityClient, \SoapClient $riskClient, Serializer $serializer)
    {
        $this->login              = $login;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->identityClient     = $identityClient;
        $this->riskClient         = $riskClient;
        $this->serializer         = $serializer;
    }

    /**
     * @param string $siren
     * @return null|CompanyRating
     */
    public function getScore($siren)
    {
        if (null !== $response = $this->riskSoapCall('getScore', ['siren' => $siren])) {
            return $this->serializer->deserialize(json_encode($response), CompanyRating::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @param int $iSheetsCount
     * @return mixed
     */
    public function getBalanceSheets($siren, $iSheetsCount = 3)
    {
        return $this->identitySoapCall('getDerniersBilans', ['siren' => $siren, 'nbBilans' => $iSheetsCount]);
    }

    /**
     * @param string $siren
     * @return null|CompanyIdentity
     * @throws \Exception
     */
    public function getCompanyIdentity($siren)
    {
        if (null !== $response = $this->identitySoapCall('getIdentiteAltaN3Entreprise', ['sirenRna' => $siren])) {
            return $this->serializer->deserialize(json_encode($response), CompanyIdentity::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @return null|FinancialSummary
     * @throws \Exception
     */
    public function getFinancialSummary($siren, $balanceId)
    {
        if (null !== $response = $this->identitySoapCall('getSyntheseFinanciere', ['siren' => $siren, 'bilanId' => $balanceId])) {
            return $this->serializer->deserialize(json_encode($response->syntheseFinanciereList), 'ArrayCollection<' . FinancialSummary::class . '>', 'json', DeserializationContext::create()->setGroups(['summary']));
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     * @return null|FinancialSummary
     * @throws \Exception
     */
    public function getBalanceManagementLine($siren, $balanceId)
    {
        if (null !== $response = $this->identitySoapCall('getSoldeIntermediaireGestion', ['siren' => $siren, 'bilanId' => $balanceId])) {
            return $this->serializer->deserialize(json_encode($response->SIGList), 'ArrayCollection<' . FinancialSummary::class . '>', 'json', DeserializationContext::create()->setGroups(['management_line']));
        }

        return null;
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

        if ($response->return->correct && $response->return->myInfo) {
            return $response->return->myInfo;
        }
        return null;
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

        if ($response->return->correct && $response->return->myInfo) {
            return $response->return->myInfo;
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
     * @param string
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
