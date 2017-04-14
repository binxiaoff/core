<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetList;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummary;

class AltaresManager
{
    const RESOURCE_COMPANY_SCORE           = 'get_score_altares';
    const RESOURCE_BALANCE_SHEET           = 'get_balance_sheet_altares';
    const RESOURCE_COMPANY_IDENTITY        = 'get_company_identity_altares';
    const RESOURCE_ESTABLISHMENT_IDENTITY  = 'get_establishment_identity_altares';
    const RESOURCE_FINANCIAL_SUMMARY       = 'get_financial_summary_altares';
    const RESOURCE_MANAGEMENT_LINE         = 'get_balance_management_line_altares';

    const EXCEPTION_CODE_UNKNOWN_SIREN_COMPANY = 101;
    const EXCEPTION_CODE_NO_FINANCIAL_DATA     = 118;

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
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $useCache = true;

    /**
     * @param string             $login
     * @param string             $password
     * @param LoggerInterface    $logger
     * @param CallHistoryManager $callHistoryManager
     * @param \SoapClient        $identityClient
     * @param \SoapClient        $riskClient
     * @param Serializer         $serializer
     * @param ResourceManager    $resourceManager
     */
    public function __construct(
        $login,
        $password,
        LoggerInterface $logger,
        CallHistoryManager $callHistoryManager,
        \SoapClient $identityClient,
        \SoapClient $riskClient,
        Serializer $serializer,
        ResourceManager $resourceManager
    )
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
     * @param bool $useCache
     *
     * @return AltaresManager
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * @param string $siren
     *
     * @return null|CompanyRating
     */
    public function getScore($siren)
    {
        if (null !== ($response = $this->soapCall('risk', self::RESOURCE_COMPANY_SCORE, ['siren' => $siren]))) {
            return $this->serializer->deserialize(json_encode($response), CompanyRating::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @param int    $balanceSheetsCount
     *
     * @return null|BalanceSheetList
     */
    public function getBalanceSheets($siren, $balanceSheetsCount = 3)
    {
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_BALANCE_SHEET, ['siren' => $siren, 'nbBilans' => $balanceSheetsCount]))) {
            if (isset($response->nbBilan) && 1 === $response->nbBilan) {
                $response->bilans = [$response->bilans];
            }
            return $this->serializer->deserialize(json_encode($response), BalanceSheetList::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     *
     * @return null|CompanyIdentity
     *
     * @throws \Exception
     */
    public function getCompanyIdentity($siren)
    {
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_COMPANY_IDENTITY, ['sirenRna' => $siren]))) {
            return $this->serializer->deserialize(json_encode($response), CompanyIdentity::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     *
     * @return null|EstablishmentIdentity
     *
     * @throws \Exception
     */
    public function getEstablishmentIdentity($siren)
    {
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_ESTABLISHMENT_IDENTITY, ['sirenSiret' => $siren]))) {
            return $this->serializer->deserialize(json_encode($response), EstablishmentIdentity::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     *
     * @return null|FinancialSummary[]
     *
     * @throws \Exception
     */
    public function getFinancialSummary($siren, $balanceId)
    {
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_FINANCIAL_SUMMARY, ['siren' => $siren, 'bilanId' => $balanceId]))) {
            return $this->serializer->deserialize(json_encode($response->syntheseFinanciereList), 'ArrayCollection<' . FinancialSummary::class . '>', 'json', DeserializationContext::create()->setGroups(['summary']));
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $balanceId
     *
     * @return null|FinancialSummary[]
     *
     * @throws \Exception
     */
    public function getBalanceManagementLine($siren, $balanceId)
    {
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_MANAGEMENT_LINE, ['siren' => $siren, 'bilanId' => $balanceId]))) {
            return $this->serializer->deserialize(json_encode($response->SIGList), 'ArrayCollection<' . FinancialSummary::class . '>', 'json', DeserializationContext::create()->setGroups(['management_line']));
        }

        return null;
    }

    /**
     * @param string $client
     * @param string $resourceLabel
     * @param array  $params
     *
     * @return mixed
     */
    private function soapCall($client, $resourceLabel, $params)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $siren      = $params[$this->getSirenKey($wsResource->resource_name)];

        try {
            $response = $this->useCache ? $this->callHistoryManager->getStoredResponse($wsResource, $siren) : false;

            if (false === $this->isValidResponse($response, $resourceLabel)) {
                $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache);
                ini_set('default_socket_timeout', 8);

                /** @var \SoapClient $soapClient */
                $soapClient = $this->{$client . 'Client'};
                $response   = $soapClient->__soapCall(
                    $wsResource->resource_name, [
                    ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
                ]);

                call_user_func($callable, json_encode($response));
            } else {
                $response = json_decode($response);
            }

            if (null !== $response) {
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');

                if ($this->isValidResponse($response, $resourceLabel)) {
                    return isset($response->return->myInfo) ? $response->return->myInfo : null;
                }
            }

            $this->logger->error(
                'Altares response could not be handled: "' . (isset($response->return->exception->description) ? $response->return->exception->description : print_r($response, true)) . '"',
                ['class' => __CLASS__, 'resource' => $resourceLabel] + $params
            );
            return null;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['class' => __CLASS__, 'resource' => $resourceLabel] + $params);
        }

        $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down');
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
     * @param mixed  $response
     * @param string $resourceLabel
     *
     * @return bool
     */
    private function isValidResponse($response, $resourceLabel)
    {
        if (is_string($response)) {
            $response = json_decode($response);
        }

        return (
            isset($response->return->myInfo, $response->return->correct) && $response->return->correct
            || isset($response->return->exception->code) && self::EXCEPTION_CODE_NO_FINANCIAL_DATA == $response->return->exception->code
            || isset($response->return->exception->code) && self::RESOURCE_ESTABLISHMENT_IDENTITY === $resourceLabel
        );
    }
}
