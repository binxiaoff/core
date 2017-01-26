<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;

use SoapClient;
use Unilend\Bundle\WSClientBundle\Entity\Infogreffe\CompanyIndebtedness;

class InfogreffeManager
{
    const PRIVILEGES_SECU_REGIMES_COMPLEMENT = '03';
    const PRIVILEGES_TRESOR_PUBLIC           = '04';
    const RESOURCE_INDEBTEDNESS              = 'get_indebtedness_infogreffe';

    /** @var  string */
    private $login;
    /** @var  string */
    private $password;
    /** @var  LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var \SoapClient */
    private $client;
    /** @var Serializer */
    private $serializer;
    /** @var bool */
    private $monitoring;
    /** @var ResourceManager */
    private $resourceManager;

    /**
     * InfogreffeManager constructor.
     * @param string $login
     * @param string $password
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param SoapClient $client
     * @param Serializer $serializer
     * @param ResourceManager $resourceManager
     */
    public function __construct($login, $password, LoggerInterface $logger, CallHistoryManager $callHistoryManager, SoapClient $client, Serializer $serializer, ResourceManager $resourceManager)
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
     * @param boolean $activate
     */
    public function setMonitoring($activate)
    {
        $this->monitoring = $activate;
    }

    /**
     * @param $siren
     * @return null|array|CompanyIndebtedness
     */
    public function getIndebtedness($siren)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('Siren is missing');
        }
        $extraInfo  = '';
        $result     = null;
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];
        $xml        = new \SimpleXMLElement('<xml/>');
        $request    = $this->addRequestHeader($xml, 'PN', 'XL');
        $order      = $request->addChild('commande');
        $order->addChild('num_siren', $siren);
        $order->addChild('type_inscription', self::PRIVILEGES_SECU_REGIMES_COMPLEMENT . self::PRIVILEGES_TRESOR_PUBLIC);
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_INDEBTEDNESS);

        /** @var callable $callBack */
        $callBack = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren);
        try {
            ini_set("default_socket_timeout", 8);
            $response  = $this->client->__soapCall($wsResource->resource_name, [$request->asXML()]);
            $alertType = 'up';
        } catch (\SoapFault $exception) {
            $alertType = 'down';
            $this->logger->warning('Calling infogreffe Indebtedness siren: ' . $siren . '. Message: ' . $exception->getMessage() . ' Code: ' . $exception->getCode(), $logContext);
        }
        call_user_func($callBack, $this->client->__getLastResponse());

        if (isset($response) && preg_match('/(^\d{3}) (.*)/', $response, $matches)) {
            if (isset($matches[1], $matches[2])) {
                if (true === in_array($matches[1], ['010', '012', '014', '999'])) {
                    $alertType = 'down';
                    $extraInfo = $matches[2];
                }
                $result = ['code' => $matches[1], 'message' => $matches[2]];
            }
        } else {
            try {
                /** @var \SimpleXMLElement $xmlResponse */
                $xmlResponse = new \SimpleXMLElement($this->client->__getLastResponse());
                /** @var \SimpleXMLElement[] $indebtedness */
                $indebtedness = $xmlResponse->xpath('//return');

                if (false !== $indebtedness) {
                    $responseArray = $this->xml2array($indebtedness[0]);
                    $this->logger->info('Extracted Array: ' . json_encode($responseArray), $logContext);

                    $result = $this->serializer->deserialize($this->getSubscription_3_4($responseArray), CompanyIndebtedness::class, 'json');
                }
            } catch (\Exception $exception) {
                $this->logger->error('Could not get response from Infogreffe getProduitsWebServicesXML to get Indebtedness. Siren: ' . $siren . '. Message: ' . $exception->getMessage(), $logContext);
            }
        }

        if ($this->monitoring) {
            $this->callHistoryManager->sendMonitoringAlert($wsResource, $alertType, $extraInfo);
        }

        return $result;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param $documentType
     * @param $responseType
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
