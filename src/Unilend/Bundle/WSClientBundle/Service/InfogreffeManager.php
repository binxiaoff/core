<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;

use SoapClient;
use Unilend\Bundle\WSClientBundle\Entity\Infogreffe\CompanyIndebtedness;

class InfogreffeManager
{
    const PRIVILEGES_SECU_REGIMES_COMPLEMENT   = '03';
    const PRIVILEGES_TRESOR_PUBLIC             = '04';

    const RESOURCE_INDEBTEDNESS                = 'get_indebtedness_infogreffe';

    const RETURN_CODE_UNKNOWN_SIREN            = '006';
    const RETURN_CODE_UNAVAILABLE_INDEBTEDNESS = '009';
    const RETURN_CODE_NO_DEBTOR                = '013';

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

    /**
     * @param string             $login
     * @param string             $password
     * @param LoggerInterface    $logger
     * @param CallHistoryManager $callHistoryManager
     * @param SoapClient         $client
     * @param Serializer         $serializer
     * @param ResourceManager $resourceManager
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
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];

        $response = $this->callHistoryManager->getStoredResponse($wsResource, $siren);
        $result   = $this->extractResponse($response);

        if (false === $this->isValidResult($result)) {
            /** @var callable $callBack */
            $callBack = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren);

            try {
                $xml     = new \SimpleXMLElement('<xml/>');
                $request = $this->addRequestHeader($xml, 'PN', 'XL');
                $order   = $request->addChild('commande');
                $order->addChild('num_siren', $siren);
                $order->addChild('type_inscription', self::PRIVILEGES_SECU_REGIMES_COMPLEMENT . self::PRIVILEGES_TRESOR_PUBLIC);

                ini_set('default_socket_timeout', 8);

                $this->client->__soapCall($wsResource->resource_name, [$request->asXML()]);
            } catch (\SoapFault $exception) {
                $this->logger->error('Calling Infogreffe Indebtedness SIREN: ' . $siren . '. Message: ' . $exception->getMessage() . ' Code: ' . $exception->getCode(), $logContext);
            }

            call_user_func($callBack, $this->client->__getLastResponse());
            $response = $this->client->__getLastResponse();
            $result   = $this->extractResponse($response, $logContext);
        }

        if (false === $this->isValidResult($result)) {
            $extraInfo  = is_array($result) && isset($result['message']) ? $result['message'] : '';
            $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down', $extraInfo);

            throw new \Exception('Invalid response from Infogreffe');
        }

        $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');
        return $result;
    }

    /**
     * @param string     $response
     * @param null|array $logContext
     *
     * @return null|array|CompanyIndebtedness
     */
    private function extractResponse($response, $logContext = null)
    {
        try {
            $xmlResponse = new \SimpleXMLElement($response);
            /** @var \SimpleXMLElement[] $indebtedness */
            $indebtedness = $xmlResponse->xpath('//return');
        } catch (\Exception $exception) {
            if ($logContext) {
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
                if ($logContext) {
                    $this->logger->error('Could not deserialize response from Infogreffe getProduitsWebServicesXML to get Indebtedness. Message: ' . $exception->getMessage(), $logContext);
                }
            }
        }

        return null;
    }

    /**
     * @param null|array|CompanyIndebtedness $response
     *
     * @return bool
     */
    private function isValidResult($response)
    {
        return (
            $response instanceof CompanyIndebtedness
            || is_array($response) && in_array($response['code'], [self::RETURN_CODE_UNKNOWN_SIREN, self::RETURN_CODE_UNAVAILABLE_INDEBTEDNESS, self::RETURN_CODE_NO_DEBTOR])
        );
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
                    $subscriptionList['inscription_3'] = $debtor['inscription_3'];
                }

                if (is_array($debtor) && array_key_exists('inscription_4', $debtor)) {
                    $subscriptionList['inscription_4'] = $debtor['inscription_4'];
                }
            }
        }

        return json_encode($subscriptionList);
    }
}
