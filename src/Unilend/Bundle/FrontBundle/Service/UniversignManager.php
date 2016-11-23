<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\librairies\CacheKeys;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

class UniversignManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var MemcacheCachePool */
    private $cachePool;
    /** @var Router */
    private $router;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $rootDir;
    /** @var string */
    private $universignURL;

    public function __construct(EntityManager $entityManager, MemcacheCachePool $cachePool, RouterInterface $router, LoggerInterface $logger, $universignURL, $rootDir)
    {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
        $this->router        = $router;
        $this->logger        = $logger;
        $this->universignURL = $universignURL;
        $this->rootDir       = $rootDir;
    }

    /**
     * @param \projects_pouvoir $proxy
     * @return bool
     */
    public function createProxy(\projects_pouvoir $proxy)
    {
        $this->logger->notice('Proxy status: ' . $proxy->status . ' - Creation of PDF to send to Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($this->getPdfParameters('proxy', $proxy->id_mandat), "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        $this->logger->notice('Proxy sent to Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

        if (! $soapResult->faultCode()) {
            $proxy->id_universign  = $soapResult->value()->structMem('id')->scalarVal();
            $proxy->url_universign = $soapResult->value()->structMem('url')->scalarVal();
            $proxy->status         = \projects_pouvoir::STATUS_PENDING;
            $proxy->update();

            return true;
        } else {
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('DebugMailIt', 'type');
            $debugMailITAddress = $settings->value;
            mail($debugMailITAddress, 'unilend erreur universign reception', 'id mandat : ' . $proxy->id_pouvoir . ' | An error occurred: Code: ' . $soapResult->faultCode() . ' Reason: "' . $soapResult->faultString());

            return false;
        }
    }

    /**
     * @param \projects_pouvoir $proxy
     * @return bool
     */
    public function signProxy(\projects_pouvoir $proxy)
    {
        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.getDocumentsByTransactionId', [new Value($proxy->id_universign, "string")]);
        $soapResult  = $soapClient->send($soapRequest);

        $this->logger->notice('Proxy sent to Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

        if (! $soapResult->faultCode()) {
            $doc['name']    = $soapResult->value()->arrayMem(0)->structMem('name')->scalarVal();
            $doc['content'] = $soapResult->value()->arrayMem(0)->structMem('content')->scalarVal();

            file_put_contents($this->rootDir . '/../protected/pdf/pouvoir/' . $doc['name'], $doc['content']);
            $proxy->status = \projects_pouvoir::STATUS_SIGNED;
            $proxy->update();

            $this->logger->notice('Proxy OK (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            /** @var \clients_mandats $mandate */
            $mandate = $this->entityManager->getRepository('clients_mandats');
            if ($mandate->get($proxy->id_project, 'id_project') && $mandate->status == \clients_mandats::STATUS_SIGNED) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendProxyAndMandateSigned($proxy, $mandate);

                $this->logger->notice('Proxy and mandate OK (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
            } else {
                $this->logger->notice('Proxy OK and mandate not signed (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
            }
        } else {
            $this->logger->error('Proxy NOK (project ' . $proxy->id_project . ') - Error code: ' . $soapResult->faultCode() . ' - Error message: ' . $soapResult->faultString(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            /** @var \settings $setting */
            $setting = $this->entityManager->getRepository('settings');
            $setting->get('DebugMailIt', 'type');
            $debugMailITAddress = $setting->value;
            mail($debugMailITAddress, 'unilend erreur universign reception', 'id pouvoir : ' . $proxy->id_pouvoir . ' | An error occurred: Code: ' . $soapResult->faultCode() . ' Reason: "' . $soapResult->faultString());
        }
    }

    /**
     * @param \clients_mandats $mandate
     * @return bool
     */
    public function createMandate(\clients_mandats $mandate)
    {
        $this->logger->notice('Mandate status: ' . $mandate->status . ' - Creation of PDF to send to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($this->getPdfParameters('mandate', $mandate->id_mandat), "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        $this->logger->notice('Mandate sent to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

        if (! $soapResult->faultCode()) {
            $url = $soapResult->value()->structMem('url')->scalarVal();
            $id  = $soapResult->value()->structMem('id')->scalarVal();

            /** @var \companies $company */
            $company = $this->entityManager->getRepository('companies');
            $company->get($mandate->id_client, 'id_client_owner');

            $mandate->id_universign  = $id;
            $mandate->url_universign = $url;
            $mandate->status         = \clients_mandats::STATUS_PENDING;
            $mandate->bic            = $company->bic;
            $mandate->iban           = $company->iban;
            $mandate->update();

            return true;
        }

        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('DebugMailIt', 'type');
        $debugMailITAddress = $settings->value;
        mail($debugMailITAddress, 'unilend erreur universign reception', ' creatioon mandat id mandat : ' . $mandate->id_mandat . ' | An error occurred: Code: ' . $soapResult->faultCode() . ' Reason: "' . $soapResult->faultString());

        return false;
    }

    /**
     * @param \clients_mandats $mandate
     */
    public function signMandate(\clients_mandats $mandate) // TODO : verify access conditions to this
    {
        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.getDocumentsByTransactionId', [new Value($mandate->id_universign, "string")]);
        $soapResult  = $soapClient->send($soapRequest);

        $this->logger->notice('Mandate sent to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

        if (! $soapResult->faultCode()) {
            $doc['name']    = $soapResult->value()->arrayMem(0)->structMem('name')->scalarVal();
            $doc['content'] = $soapResult->value()->arrayMem(0)->structMem('content')->scalarVal();

            file_put_contents($this->rootDir . '/../protected/pdf/mandat/' . $doc['name'], $doc['content']);
            $mandate->status = \clients_mandats::STATUS_SIGNED;
            $mandate->update();

            $this->logger->notice('Mandate OK (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

            /** @var \projects_pouvoir $proxy */
            $proxy = $this->entityManager->getRepository('projects_pouvoir');
            if ($proxy->get($mandate->id_project, 'id_project') && $proxy->status == \projects_pouvoir::STATUS_SIGNED) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendProxyAndMandateSigned($proxy, $mandate);

                $this->logger->notice('Mandate and proxy OK (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
            } else {
                $this->logger->notice('Mandate OK - proxy not signed (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
            }
        } else {
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('DebugMailIt', 'type');
            $sDestinatairesDebug = $settings->value;

            $this->logger->error('Return Universign mandate NOK (project ' . $mandate->id_project . ') - Errorr code : ' . $soapResult->faultCode() . ' - Error Message : ' . $soapResult->faultString(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
            mail($sDestinatairesDebug, 'unilend erreur universign reception', 'id mandat : ' . $mandate->id_mandat . ' | An error occurred: Code: ' . $soapResult->faultCode() . ' Reason: "' . $soapResult->faultString());
        }
    }

    /**
     * @param string $documentType
     * @param string $documentId
     * @return array
     */
    public function getPdfParameters($documentType, $documentId)
    {
        switch ($documentType) {
            case 'mandate':
                /** @var \projects_pouvoir $mandate */
                $mandate = $this->entityManager->getRepository('clients_mandats');
                $mandate->get($documentId);
                /** @var \clients $client */
                $client = $this->entityManager->getRepository('clients');
                $client->get($mandate->id_client, 'id_client');
                $documentName = $mandate->name;
                $routeName    = 'mandate_signature_status';
                $doc_name     = $this->rootDir . '/../protected/pdf/mandat/' . $documentName;
                break;
            case 'proxy':
                /** @var \projects_pouvoir $proxy */
                $proxy = $this->entityManager->getRepository('projects_pouvoir');
                $proxy->get($documentId);
                /** @var \clients $client */
                $client = $this->entityManager->getRepository('clients');
                $client->get($proxy->id_client, 'id_client');
                $documentName = $proxy->name;
                $routeName    = 'proxy_signature_status';
                $doc_name     = $this->rootDir . '/../protected/pdf/pouvoir/' . $documentName;
                break;
            default:
                return [];
        }

        $returnPage  = [
            'success' => $this->router->generate($routeName, ['status' => 'success', 'documentId' => $documentId], 0),
            'fail'    => $this->router->generate($routeName, ['status' => 'fail', 'documentId' => $documentId], 0),
            'cancel'  => $this->router->generate($routeName, ['status' => 'cancel', 'documentId' => $documentId], 0)
        ];

        // signature position
        $docSignatureField = [
            "page"        => new Value(1, "int"),
            "x"           => new Value(255, "int"),
            "y"           => new Value(314, "int"),
            "signerIndex" => new Value(0, "int"),
            "label"       => new Value("Unilend", "string")
        ];

        $signer = [
            "firstname"    => new Value($client->prenom, "string"),
            "lastname"     => new Value($client->nom, "string"),
            "phoneNum"     => new Value(str_replace(' ', '', $client->telephone), "string"),
            "emailAddress" => new Value($client->email, "string")
        ];

        $doc = [
            "content"         => new Value(file_get_contents($doc_name), "base64"),
            "name"            => new Value($documentName, "string"),
            "signatureFields" => new Value([new Value($docSignatureField, "struct")], "array")
        ];

        return [
            "documents"          => new Value([new Value($doc, "struct")], "array"),
            "signers"            => new Value([new Value($signer, "struct")], "array"),
            "successURL"         => new Value($returnPage["success"], "string"),
            "failURL"            => new Value($returnPage["fail"], "string"),
            "cancelURL"          => new Value($returnPage["cancel"], "string"),
            "certificateTypes"   => new Value([new Value("timestamp", "string")], "array"),
            "language"           => new Value("fr", "string"),
            "identificationType" => new Value("sms", "string"),
            "description"        => new Value("Document id : " . $documentId, "string")
        ];
    }
}
