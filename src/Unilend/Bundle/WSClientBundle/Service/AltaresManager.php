<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetList;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetListDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentityDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRatingDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentityDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummary;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummaryListDetail;

class AltaresManager
{
    const RESOURCE_COMPANY_SCORE          = 'get_score_altares';
    const RESOURCE_BALANCE_SHEET          = 'get_balance_sheet_altares';
    const RESOURCE_COMPANY_IDENTITY       = 'get_company_identity_altares';
    const RESOURCE_ESTABLISHMENT_IDENTITY = 'get_establishment_identity_altares';
    const RESOURCE_FINANCIAL_SUMMARY      = 'get_financial_summary_altares';
    const RESOURCE_MANAGEMENT_LINE        = 'get_balance_management_line_altares';

    const EXCEPTION_CODE_INVALID_OR_UNKNOWN_SIREN = [101, 102, 108, 109, 106];
    const EXCEPTION_CODE_NO_FINANCIAL_DATA        = [118];
    const EXCEPTION_CODE_TECHNICAL_ERROR          = [-1, 0, 1, 2, 3, 4, 5, 7, 8];

    const CALL_TIMEOUT = 8;

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
     * @return CompanyRatingDetail
     */
    public function getScore($siren)
    {
        if (null !== ($response = $this->soapCall('risk', self::RESOURCE_COMPANY_SCORE, ['siren' => $siren]))) {
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
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_BALANCE_SHEET, ['siren' => $siren, 'nbBilans' => $balanceSheetsCount]))) {
            if (isset($response->return->myInfo->nbBilan) && 1 === $response->return->myInfo->nbBilan) {
                $response->return->myInfo->bilans = [$response->return->myInfo->bilans];
            }
            /** @var BalanceSheetList $balanceSheetList */
            $balanceSheetList =  $this->serializer->deserialize(json_encode($response->return), BalanceSheetList::class, 'json');

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
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_COMPANY_IDENTITY, ['sirenRna' => $siren]))) {
            /** @var CompanyIdentity $companyIdentity */
            $companyIdentity = $this->serializer->deserialize(json_encode($response->return), CompanyIdentity::class, 'json');

            return $companyIdentity->getMyInfo();
        }

        return null;
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
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_ESTABLISHMENT_IDENTITY, ['sirenSiret' => $siren]))) {
            /** @var EstablishmentIdentity $establishmentIdentity */
            $establishmentIdentity = $this->serializer->deserialize(json_encode($response->return), EstablishmentIdentity::class, 'json');

            $establishmentIdentity->getMyInfo();
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
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_FINANCIAL_SUMMARY, ['siren' => $siren, 'bilanId' => $balanceId]))) {
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
        if (null !== ($response = $this->soapCall('identity', self::RESOURCE_MANAGEMENT_LINE, ['siren' => $siren, 'bilanId' => $balanceId]))) {
            /** @var FinancialSummary $financialSummaryListDetail */
            $balanceManagementLine = $this->serializer->deserialize(json_encode($response->return), FinancialSummary::class, 'json');

            return $balanceManagementLine->getMyInfo();
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
    private function soapCall($client, $resourceLabel, array $params)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $siren      = $params[$this->getSirenKey($wsResource->getResourceName())];

        try {
            if ($storedResponse = $this->getStoredResponse($wsResource, $siren)) {
                if ($this->isValidResponse($storedResponse)['is_valid']) {
                    return $storedResponse;
                }
            }
            $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache);
            ini_set('default_socket_timeout', self::CALL_TIMEOUT);

            /** @var \SoapClient $soapClient */
            $soapClient = $this->{$client . 'Client'};
            $response   = $soapClient->__soapCall(
                $wsResource->getResourceName(), [
                ['identification' => $this->getIdentification(), 'refClient' => 'sffpme'] + $params
            ]);

            $validity = $this->isValidResponse($response, ['class' => __CLASS__, 'resource' => $wsResource->getLabel()] + $params);
            call_user_func($callable, json_encode($response), $validity['status']);

            if ($validity['is_valid']) {
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');
                return $response;
            }
        } catch (\Exception $exception) {
            if (isset($callable)) {
                call_user_func($callable, isset($response) ? json_encode($response) : null, 'error');
            }
            $this->logger->error($exception->getMessage() . ' - Code ' . $exception->getCode(),
                ['class' => __CLASS__, 'resource' => $wsResource->getLabel()] + $params
            );
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
     * @param mixed $response
     * @param array $logContext
     *
     * @return array
     *
     * @throws \Exception
     */
    private function isValidResponse($response, array $logContext = [])
    {
        if (false === isset($response->return)
            || (false === isset($response->return->exception) && false === isset($response->return->myInfo))
            || (isset($response->return->exception, $response->return->myInfo))
        ) {
            if (false === empty($logContext)) {
                $this->logger->error('Altares response could not be handled: "' . json_encode($response) . '"', $logContext);
            }
            return ['status' => 'error', 'is_valid' => false];
        }

        if (isset($response->return->exception)) {
            if (in_array($response->return->exception->code, self::EXCEPTION_CODE_INVALID_OR_UNKNOWN_SIREN + self::EXCEPTION_CODE_NO_FINANCIAL_DATA)) {
                return ['status' => 'valid', 'is_valid' => true];
            } elseif (in_array($response->return->exception->code, self::EXCEPTION_CODE_TECHNICAL_ERROR)) {
                throw new \Exception('Altares response technical error: "' . $response->return->exception->description . '"', $response->return->exception->code);
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
            $this->logger->warning('Altares response not expected: "' . json_encode($response) . '"', $logContext);
        }
        return ['status' => 'warning', 'is_valid' => false];
    }

    /**
     * @param WsExternalResource $resource
     * @param string             $siren
     *
     * @return mixed;
     */
    private function getStoredResponse($resource, $siren)
    {
        if ($this->useCache
            && false !== ($storedResponse = $this->callHistoryManager->getStoredResponse($resource, $siren))
            && null !== ($storedResponse = json_decode($storedResponse))
            && isset($storedResponse->return)
        ) {
            return $storedResponse;
        }

        return false;
    }
}
