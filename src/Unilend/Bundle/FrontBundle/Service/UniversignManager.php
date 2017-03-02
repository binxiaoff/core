<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

class UniversignManager
{
    /** @var EntityManagerSimulator */
    private $entityManager;
    /** @var Router */
    private $router;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $rootDir;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var string */
    private $universignURL;
    /** @var MailerManager */
    private $mailerManager;

    public function __construct(
        EntityManagerSimulator $entityManager,
        MailerManager $mailerManager,
        RouterInterface $router,
        LoggerInterface $logger,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        $universignURL,
        $rootDir
    )
    {
        $this->entityManager   = $entityManager;
        $this->mailerManager   = $mailerManager;
        $this->router          = $router;
        $this->logger          = $logger;
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
        $this->universignURL   = $universignURL;
        $this->rootDir         = $rootDir;
    }

    /**
     * @param \projects_pouvoir $proxy
     * @return bool
     */
    public function createProxy(\projects_pouvoir $proxy)
    {
        try {
            $pdfParameters = $this->getPdfParameters('proxy', $proxy->id_pouvoir);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($proxy->id_pouvoir, 'proxy', $proxy->id_project, $soapResult);

            return false;
        }
        $resultValue           = $soapResult->value();
        $proxy->id_universign  = $resultValue['id']->scalarVal();
        $proxy->url_universign = $resultValue['url']->scalarVal();
        $proxy->status         = \projects_pouvoir::STATUS_PENDING;
        $proxy->update();

        return true;
    }

    /**
     * @param \projects_pouvoir $proxy
     */
    public function signProxy(\projects_pouvoir $proxy)
    {
        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.getDocumentsByTransactionId', [new Value($proxy->id_universign, "string")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($proxy->id_pouvoir, 'proxy', $proxy->id_project, $soapResult);
        } else {
            $resultValue     = $soapResult->value()[0];
            $documentName    = $resultValue['name']->scalarVal();
            $documentContent = $resultValue['content']->scalarVal();

            file_put_contents($this->rootDir . '/../protected/pdf/pouvoir/' . $documentName, $documentContent);
            $proxy->status = \projects_pouvoir::STATUS_SIGNED;
            $proxy->update();

            /** @var \clients_mandats $mandate */
            $mandate = $this->entityManager->getRepository('clients_mandats');
            if ($mandate->get($proxy->id_project, ' status = '. \clients_mandats::STATUS_SIGNED . ' AND id_project')) {
                $this->mailerManager->sendProxyAndMandateSigned($proxy, $mandate);
            }
        }
    }

    /**
     * @param \clients_mandats $mandate
     * @return bool
     */
    public function createMandate(\clients_mandats $mandate)
    {
        try {
            $pdfParameters = $this->getPdfParameters('mandate', $mandate->id_mandat);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($mandate->id_mandat, 'mandate', $mandate->id_project, $soapResult);

            return false;
        }

        $resultValue = $soapResult->value();
        $url         = $resultValue['url']->scalarVal();
        $id          = $resultValue['id']->scalarVal();

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

    /**
     * @param \clients_mandats $mandate
     */
    public function signMandate(\clients_mandats $mandate)
    {
        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.getDocumentsByTransactionId', [new Value($mandate->id_universign, "string")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($mandate->id_mandat, 'mandate', $mandate->id_project, $soapResult);
        } else {
            $resultValue     = $soapResult->value()[0];
            $documentName    = $resultValue['name']->scalarVal();
            $documentContent = $resultValue['content']->scalarVal();

            file_put_contents($this->rootDir . '/../protected/pdf/mandat/' . $documentName, $documentContent);
            $mandate->status = \clients_mandats::STATUS_SIGNED;
            $mandate->update();

            if ($mandate->exist($mandate->id_client, 'id_project = ' . $mandate->id_project . ' AND status = ' . \clients_mandats::STATUS_ARCHIVED . ' AND id_client')) {
                $this->updateDirectDebit($mandate);
            }

            /** @var \projects_pouvoir $proxy */
            $proxy = $this->entityManager->getRepository('projects_pouvoir');

            if ($proxy->get($mandate->id_project, 'id_project') && $proxy->status == \projects_pouvoir::STATUS_SIGNED) {
                $this->mailerManager->sendProxyAndMandateSigned($proxy, $mandate);
            }
        }
    }

    /**
     * @param \project_cgv $tos
     * @return bool
     */
    public function createTOS(\project_cgv $tos)
    {
        try {
            $pdfParameters = $this->getPdfParameters('tos', $tos->id);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($tos->id_mandat, 'tos', $tos->id_project, $soapResult);

            return false;
        }

        $resultValue         = $soapResult->value();
        $tos->id_universign  = $resultValue['id']->scalarVal();
        $tos->url_universign = $resultValue['url']->scalarVal();
        $tos->update();

        return true;
    }

    /**
     * @param \project_cgv $tos
     */
    public function signTos(\project_cgv $tos)
    {
        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.getDocumentsByTransactionId', [new Value($tos->id_universign, "string")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($tos->id, 'tos', $tos->id_project, $soapResult);
        } else {
            $resultValue     = $soapResult->value()[0];
            $documentName    = $resultValue['name']->scalarVal();
            $documentContent = $resultValue['content']->scalarVal();

            file_put_contents($this->rootDir . '/../protected/pdf/cgv_emprunteurs/' . $documentName, $documentContent);
            $tos->status = \project_cgv::STATUS_SIGN_UNIVERSIGN;
            $tos->update();
        }
    }

    /**
     * @param string $documentType
     * @param string $documentId
     * @return array
     * @throws \Exception
     */
    private function getPdfParameters($documentType, $documentId)
    {
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');

        switch ($documentType) {
            case 'mandate':
                /** @var \clients_mandats $mandate */
                $mandate = $this->entityManager->getRepository('clients_mandats');
                $mandate->get($documentId);
                $client->get($mandate->id_client, 'id_client');
                $documentName = $mandate->name;
                $routeName    = 'mandate_signature_status';
                $doc_name     = $this->rootDir . '/../protected/pdf/mandat/' . $documentName;
                break;
            case 'proxy':
                /** @var \projects_pouvoir $proxy */
                $proxy = $this->entityManager->getRepository('projects_pouvoir');
                $proxy->get($documentId);
                $project->get($proxy->id_project);
                $company->get($project->id_company);
                $client->get($company->id_client_owner, 'id_client');
                $documentName = $proxy->name;
                $routeName    = 'proxy_signature_status';
                $doc_name     = $this->rootDir . '/../protected/pdf/pouvoir/' . $documentName;
                break;
            case 'tos':
                /** @var \project_cgv $tos */
                $tos = $this->entityManager->getRepository('project_cgv');
                $tos->get($documentId);
                $project->get($tos->id_project);
                $company->get($project->id_company);
                $client->get($company->id_client_owner, 'id_client');
                $documentName = $tos->name;
                $routeName    = 'tos_signature_status';
                $doc_name     = $this->rootDir . '/../protected/pdf/cgv_emprunteurs/' . $documentName;
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . $documentType . '  id : ' . $documentId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . $documentType . '  id : ' . $documentId);
        }

        $returnPage = [
            'success' => $this->router->generate($routeName, ['status' => 'success', 'documentId' => $documentId, 'clientHash' => $client->hash], 0),
            'fail'    => $this->router->generate($routeName, ['status' => 'fail', 'documentId' => $documentId, 'clientHash' => $client->hash], 0),
            'cancel'  => $this->router->generate($routeName, ['status' => 'cancel', 'documentId' => $documentId, 'clientHash' => $client->hash], 0)
        ];

        $docSignatureField = [
            'page'        => new Value(1, 'int'),
            'x'           => new Value($documentType == 'tos' ? 430 : 255, 'int'),
            'y'           => new Value($documentType == 'tos' ? 750 : 314, 'int'),
            'signerIndex' => new Value(0, 'int'),
            'label'       => new Value('Unilend', 'string')
        ];

        $signer = [
            'firstname'    => new Value($client->prenom, 'string'),
            'lastname'     => new Value($client->nom, 'string'),
            'phoneNum'     => new Value(str_replace(' ', '', $client->telephone), 'string'),
            'emailAddress' => new Value($client->email, 'string')
        ];

        $doc = [
            'content'         => new Value(file_get_contents($doc_name), 'base64'),
            'name'            => new Value($documentName, 'string'),
            'signatureFields' => new Value([new Value($docSignatureField, 'struct')], 'array')
        ];

        return [
            'documents'          => new Value([new Value($doc, 'struct')], 'array'),
            'signers'            => new Value([new Value($signer, 'struct')], 'array'),
            'successURL'         => new Value($returnPage['success'], 'string'),
            'failURL'            => new Value($returnPage['fail'], 'string'),
            'cancelURL'          => new Value($returnPage['cancel'], 'string'),
            'certificateTypes'   => new Value([new Value('timestamp', 'string')], 'array'),
            'language'           => new Value('fr', 'string'),
            'identificationType' => new Value('sms', 'string'),
            'description'        => new Value('Document id : ' . $documentId, 'string')
        ];
    }

    /**
     * @param string $documentId
     * @param string $documentType
     * @param string $projectId
     * @param \PhpXmlRpc\Response $soapResult
     */
    private function notifyError($documentId, $documentType, $projectId, $soapResult)
    {
        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('DebugMailIt', 'type');

        $varMail = [
            '[DOCUMENT_TYPE]'     => $documentType,
            '[DOCUMENT_ID]'       => $documentId,
            '[SOAP_ERROR_CODE]'   => $soapResult->faultCode(),
            '[SOAP_ERROR_REASON]' => $soapResult->faultString()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-erreur-universign', $varMail, false);
        $message->setTo($settings->value);
        $this->mailer->send($message);

        $this->logger->error('Return Universign ' . $documentType . ' NOK (project ' . $projectId . ') - Error code : ' . $soapResult->faultCode() . ' - Error Message : ' . $soapResult->faultString(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $projectId]);
    }

    /**
     * @param \clients_mandats $activeMandate
     */
    private function updateDirectDebit(\clients_mandats $activeMandate)
    {
        /** @var \receptions $directDebit */
        $directDebit = $this->entityManager->getRepository('prelevements');

        $futureDirectDebits = $directDebit->select('id_client = ' . $activeMandate->id_client . ' AND id_project = ' . $activeMandate->id_project . ' AND status = ' . \prelevements::STATUS_PENDING);

        if (false === empty($futureDirectDebits)) {
            foreach ($futureDirectDebits as $futureDebit) {
                $directDebit->get($futureDebit['id_prelevement']);
                $directDebit->iban = $activeMandate->iban;
                $directDebit->bic  = $activeMandate->bic;
                $directDebit->update();
                $directDebit->unsetData();
            }
        }
    }
}
