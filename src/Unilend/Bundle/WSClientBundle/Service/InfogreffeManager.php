<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Psr\Log\LoggerInterface;

use SoapClient;

class InfogreffeManager
{
    const PRIVILEGES_SECU_REGIMES_COMPLEMENT = '03';
    const PRIVILEGES_TRESORE_PUBLIC          = '04';

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

    /**
     * InfogreffeManager constructor.
     * @param $login
     * @param $password
     * @param LoggerInterface $logger
     * @param SoapClient $client
     * @param CallHistoryManager $callHistoryManager
     */
    public function __construct($login, $password, LoggerInterface $logger, CallHistoryManager $callHistoryManager, SoapClient $client)
    {
        $this->login              = $login;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->client             = $client;
    }

    /**
     * @param $siren
     * @return array
     */
    public function getIndebtedness($siren)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('Siren is missing');
        }
        $xml     = new \SimpleXMLElement('<xml/>');
        $request = $this->addRequestHeader($xml, 'PN', 'XL');
        $order   = $request->addChild('commande');
        $order->addChild('num_siren', $siren);
        $order->addChild('type_inscription', self::PRIVILEGES_SECU_REGIMES_COMPLEMENT . self::PRIVILEGES_TRESORE_PUBLIC);

        /** @var callable $callBack */
        $callBack = $this->callHistoryManager->addResourceCallHistoryLog('infogreffe', __FUNCTION__, 'POST', $siren);
        try {
            $this->client->__soapCall('getProduitsWebServicesXML', [$request->asXML()]);
        } catch (\SoapFault $exception) {
            $this->logger->warning('Calling infogreffe Indebtedness siren: ' . $siren . '. Message: ' . $exception->getMessage() . ' Code: ' . $exception->getCode(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
        }
        call_user_func($callBack);

        try {
            /** @var \SimpleXMLElement $xmlResponse */
            $xmlResponse = new \SimpleXMLElement($this->client->__getLastResponse());
            /** @var \SimpleXMLElement[] $indebtedness */
            $indebtedness = $xmlResponse->xpath('//return');

            if (false !== $indebtedness) {
                return $this->xml2array($indebtedness[0]);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get response from Infogreffe Indebtedness ws. Siren: ' . $siren . '. Message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
        }

        return [];
    }

    /**
     * @param $siren
     * @return array
     */
    public function getKBisUrl($siren)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('Siren is missing');
        }
        $xml     = new \SimpleXMLElement('<xml/>');
        $request = $this->addRequestHeader($xml, 'KB', 'T');
        $order   = $request->addChild('commande');
        $order->addChild('num_siren', $siren);

        /** @var callable $callBack */
        $callBack = $this->callHistoryManager->addResourceCallHistoryLog('infogreffe', __FUNCTION__, 'POST', $siren);
        try {
            $this->client->__soapCall('getProduitsWebServicesXML', [$request->asXML()]);
        } catch (\SoapFault $exception) {
            $this->logger->warning('Calling infogreffe Indebtedness siren: ' . $siren . '. Message: ' . $exception->getMessage() . ' Code: ' . $exception->getCode(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
        }
        call_user_func($callBack);

        try {
            /** @var \SimpleXMLElement $xmlResponse */
            $xmlResponse = new \SimpleXMLElement($this->client->__getLastResponse());
            /** @var \SimpleXMLElement[] $indebtedness */
            $indebtedness = $xmlResponse->xpath('//return');

            if (false !== $indebtedness) {
                return $this->xml2array($indebtedness[0]);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get response from Infogreffe Indebtedness ws. Siren: ' . $siren . '. Message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
        }

        return [];
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
    function xml2array($xml)
    {
        $result = [];
        foreach ((array) $xml as $key => $node) {
            $result[$key] = (true === is_object($node) || true === is_array($node)) ? $this->xml2array($node) : $node;
        }

        return $result;
    }
}
