<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use SoapClient;
use Unilend\Bundle\WSClientBundle\Entity\Infogreffe\CompanyIndebtedness;

class InfogreffeManager
{
    const RESOURCE_INDEBTEDNESS = 'get_indebtedness_infogreffe';

    const PRIVILEGES_SECU_REGIMES_COMPLEMENT   = '03';
    const PRIVILEGES_TRESOR_PUBLIC             = '04';
    const RETURN_CODE_UNKNOWN_SIREN            = '006';
    const RETURN_CODE_INVALID_SIREN            = '024';
    const RETURN_CODE_SIREN_NOT_FOUND          = '025';
    const RETURN_CODE_UNAVAILABLE_INDEBTEDNESS = '009';
    const RETURN_CODE_NO_DEBTOR                = '013';
    const RETURN_CODE_TECHNICAL_ERROR          = ['001', '002', '003', '004', '005', '007', '010', '012', '014', '015', '065', '999'];

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
    private $client;
    /** @var Serializer */
    private $serializer;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $useCache = true;
    /** @var bool */
    private $monitoring = false;

    /**
     * @param string             $login
     * @param string             $password
     * @param LoggerInterface    $logger
     * @param CallHistoryManager $callHistoryManager
     * @param SoapClient         $client
     * @param Serializer         $serializer
     * @param ResourceManager    $resourceManager
     */
    public function __construct(
        $login,
        $password,
        LoggerInterface $logger,
        CallHistoryManager $callHistoryManager,
        SoapClient $client,
        Serializer $serializer,
        ResourceManager $resourceManager
    )
    {
        $this->login              = $login;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->client             = $client;
        $this->serializer         = $serializer;
        $this->resourceManager    = $resourceManager;
    }

    /**
     * @param bool $useCache
     *
     * @return InfogreffeManager
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * @param bool $monitoring
     *
     * @return InfogreffeManager
     */
    public function setMonitoring($monitoring)
    {
        $this->monitoring = $monitoring;

        return $this;
    }

    /**
     * @param string $siren
     *
     * @return null|array|CompanyIndebtedness
     *
     * @throws \Exception
     */
    public function getIndebtedness($siren)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('SIREN is missing');
        }

        $wsResource = $this->resourceManager->getResource(self::RESOURCE_INDEBTEDNESS);
        $logContext = ['class' => __CLASS__, 'resource' => $wsResource->getResourceName(), 'siren' => $siren];

        $response = $this->useCache ? $this->callHistoryManager->getStoredResponse($wsResource, $siren) : false;
        $result   = $response ? $this->extractResponse($response) : false;

        if ($this->isValidResponse($result)['is_valid']) {
            return $result;
        }
        $callBack = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache);

        try {
            $request = $this->getXmlRequest($siren);
            ini_set('default_socket_timeout', self::CALL_TIMEOUT);

            $this->client->__soapCall(
                $wsResource->getResourceName(),
                [$request->asXML()]
            );
        } catch (\SoapFault $exception) {
            // Infogreffe WS response does not seem to be valid. Workaround by Mesbah: ignore error and call SoapClient::__getLastResponse()
            if ('SOAP-ERROR: Encoding: Violation of encoding rules' === $exception->getMessage()) {
                // https://github.com/laravel/framework/issues/6618
                set_error_handler('var_dump', 0); // Never called because of empty mask.
                @trigger_error('');
                restore_error_handler();
            } else {
                call_user_func($callBack, $this->client->__getLastResponse(), 'error');
                $this->logger->error('Calling Infogreffe: ' . $exception->getMessage() . ' Code: ' . $exception->getCode(), $logContext);
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down', $exception->getMessage());

                return null;
            }
        }
        $response = $this->client->__getLastResponse();
        $result   = $this->extractResponse($response, $logContext);
        $validity = $this->isValidResponse($result, $logContext);
        call_user_func($callBack, $response, $validity['status']);

        if ($validity['status'] === 'error') {
            $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down');
        } else {
            $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');
        }
        if ($validity['is_valid']) {
            return $result;
        }

        return null;
    }

    /**
     * @param string $response
     * @param array  $logContext
     *
     * @return null|array|CompanyIndebtedness
     */
    private function extractResponse($response, $logContext = [])
    {
        try {
            $xmlResponse = new \SimpleXMLElement($response);
            /** @var \SimpleXMLElement[] $indebtedness */
            $indebtedness = $xmlResponse->xpath('//return');
        } catch (\Exception $exception) {
            if (false === empty($logContext)) {
                $this->logger->error('Unrecognized response from Infogreffe getProduitsWebServicesXML, could not create XML object. Message: ' . $exception->getMessage(), $logContext);
            }
        }

        if (isset($indebtedness[0]) && preg_match('/(^\d{3}) (.*)/', $indebtedness[0], $matches)) {
            if (isset($matches[1], $matches[2])) {
                return [
                    'code'    => $matches[1],
                    'message' => $matches[2]
                ];
            }
        } elseif (isset($indebtedness[0])) {
            try {
                $responseArray = $this->xml2array($indebtedness[0]);
                return $this->serializer->deserialize($this->getSubscription_3_4($responseArray), CompanyIndebtedness::class, 'json');
            } catch (\Exception $exception) {
                if (false === empty($logContext)) {
                    $this->logger->error('Could not deserialize response from Infogreffe getProduitsWebServicesXML to get Indebtedness. Message: ' . $exception->getMessage(), $logContext);
                }
            }
        }

        return null;
    }

    /**
     * @param string $siren
     *
     * @return \SimpleXMLElement
     */
    private function getXmlRequest($siren)
    {
        $xml          = new \SimpleXMLElement('<xml/>');
        $documentType = $this->monitoring ? 'LE' : 'PN';
        $request      = $this->addRequestHeader($xml, $documentType, 'XL');
        $order        = $request->addChild('commande');
        $order->addChild('num_siren', $siren);
        $order->addChild('type_inscription', self::PRIVILEGES_SECU_REGIMES_COMPLEMENT . self::PRIVILEGES_TRESOR_PUBLIC);

        return $request;
    }

    /**
     * @param null|array|CompanyIndebtedness $response
     * @param array                          $logContext
     *
     * @return array
     */
    private function isValidResponse($response, $logContext = [])
    {
        if ($response instanceof CompanyIndebtedness) {
            return ['status' => 'valid', 'is_valid' => true];
        }
        if (is_array($response) && isset($response['code'])) {
            if (in_array($response['code'], [self::RETURN_CODE_UNKNOWN_SIREN, self::RETURN_CODE_UNAVAILABLE_INDEBTEDNESS, self::RETURN_CODE_NO_DEBTOR, self::RETURN_CODE_SIREN_NOT_FOUND, self::RETURN_CODE_INVALID_SIREN])) {
                return ['status' => 'valid', 'is_valid' => true];
            } elseif (in_array($response['code'], self::RETURN_CODE_TECHNICAL_ERROR)) {
                if (false === empty($logContext)) {
                    $this->logger->error('Infogreffe technical error. Response: ' . json_encode($response), $logContext);
                }
                return ['status' => 'error', 'is_valid' => false];
            } else {
                if (false === empty($logContext)) {
                    $this->logger->warning('Unexpected Infogreffe error code. Response: ' . json_encode($response), $logContext);
                }
                return ['status' => 'warning', 'is_valid' => false];
            }
        }

        return ['status' => 'warning', 'is_valid' => false];
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param string            $documentType
     * @param string            $responseType
     *
     * @return \SimpleXMLElement
     */
    private function addRequestHeader(\SimpleXMLElement $xml, $documentType, $responseType)
    {
        $request     = $xml->addChild('demande');
        $transmitter = $request->addChild('emetteur');
        $transmitter->addChild('code_abonne', $this->login);
        $transmitter->addChild('mot_passe', $this->password);
        $requestCode = $transmitter->addChild('code_requete');
        $requestCode->addChild('type_profil', 'A');
        $requestCode->addChild('origine_emetteur', 'IC');
        $requestCode->addChild('nature_requete', 'C');
        $requestCode->addChild('type_document', $documentType);
        $requestCode->addChild('type_requete', 'S');
        $responseMod = $requestCode->addChild('mode_diffusion');
        $responseMod->addChild('mode')->addAttribute('type', $responseType);
        $requestCode->addChild('media', 'WS');

        return $request;
    }

    /**
     * @param mixed $xml
     *
     * @return array
     */
    private function xml2array($xml)
    {
        $result = [];
        foreach ((array) $xml as $key => $node) {
            $result[$key] = (true === is_object($node) || true === is_array($node)) ? $this->xml2array($node) : $node;
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return mixed|string
     */
    private function getSubscription_3_4(array $data)
    {
        $subscriptionList = [];

        if (isset($data['etat'], $data['etat']['debiteur'])) {
            if (array_key_exists('inscription_3', $data['etat']['debiteur'])) {
                $subscriptionList['inscription_3'][] = $data['etat']['debiteur']['inscription_3'];
            }

            if (array_key_exists('inscription_4', $data['etat']['debiteur'])) {
                $subscriptionList['inscription_4'][] = $data['etat']['debiteur']['inscription_4'];
            }

            if (false === empty($subscriptionList)) {
                return json_encode($subscriptionList);
            }

            foreach ($data['etat']['debiteur'] as $debtor) {
                if (is_array($debtor) && array_key_exists('inscription_3', $debtor)) {
                    $subscriptionList['inscription_3'][] = $debtor['inscription_3'];
                }

                if (is_array($debtor) && array_key_exists('inscription_4', $debtor)) {
                    $subscriptionList['inscription_4'][] = $debtor['inscription_4'];
                }
            }
        }

        return json_encode($subscriptionList);
    }
}
