<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsMandats;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\WireTransferOutUniversign;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\UniversignEntityInterface;
use \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

class UniversignManager
{
    const DOCUMENT_TYPE_WIRE_TRANSFER_OUT = 'wire_transfer_out';

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var  EntityManager */
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
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        MailerManager $mailerManager,
        RouterInterface $router,
        LoggerInterface $logger,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        $universignURL,
        $rootDir
    ) {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->mailerManager          = $mailerManager;
        $this->router                 = $router;
        $this->logger                 = $logger;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->universignURL          = $universignURL;
        $this->rootDir                = $rootDir;
    }

    /**
     * @param ProjectsPouvoir $proxy
     *
     * @return bool
     */
    public function createProxy(ProjectsPouvoir $proxy)
    {
        try {
            $pdfParameters = $this->getParameters($proxy);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($proxy->getId(), 'proxy', $soapResult);

            return false;
        }
        $resultValue = $soapResult->value();
        $proxy->setIdUniversign($resultValue['id']->scalarVal())
              ->setUrlUniversign($resultValue['url']->scalarVal())
              ->setStatus(ProjectsPouvoir::STATUS_PENDING);
        $this->entityManager->flush($proxy);

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
            $this->notifyError($proxy->id_pouvoir, 'proxy', $soapResult);
        } else {
            $resultValue     = $soapResult->value()[0];
            $documentName    = $resultValue['name']->scalarVal();
            $documentContent = $resultValue['content']->scalarVal();

            file_put_contents($this->rootDir . '/../protected/pdf/pouvoir/' . $documentName, $documentContent);
            $proxy->status = \projects_pouvoir::STATUS_SIGNED;
            $proxy->update();

            /** @var \clients_mandats $mandate */
            $mandate = $this->entityManagerSimulator->getRepository('clients_mandats');
            if ($mandate->get($proxy->id_project, ' status = ' . \clients_mandats::STATUS_SIGNED . ' AND id_project')) {
                $this->mailerManager->sendProxyAndMandateSigned($proxy, $mandate);
            }
        }
    }

    /**
     * @param ClientsMandats $mandate
     *
     * @return bool
     */
    public function createMandate(ClientsMandats $mandate)
    {
        try {
            $pdfParameters = $this->getParameters($mandate);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($mandate->getId(), 'mandate', $soapResult);

            return false;
        }

        $resultValue = $soapResult->value();
        $url         = $resultValue['url']->scalarVal();
        $id          = $resultValue['id']->scalarVal();

        $bankAccount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($mandate->getIdClient());
        if (null === $bankAccount) {
            $this->logger->warning('No validated bank account found for mandat : ' . $mandate->getId() . ' of client : ' . $mandate->getIdClient()->getIdClient(), ['function' => __FUNCTION__]);

            return false;
        }

        $mandate->setIdUniversign($id)
                ->setUrlUniversign($url)
                ->setStatus(ClientsMandats::STATUS_PENDING)
                ->setBic($bankAccount->getBic())
                ->setIban($bankAccount->getIban());
        $this->entityManager->flush($mandate);

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
            $this->notifyError($mandate->id_mandat, 'mandate', $soapResult);
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
            $proxy = $this->entityManagerSimulator->getRepository('projects_pouvoir');

            if ($proxy->get($mandate->id_project, 'id_project') && $proxy->status == \projects_pouvoir::STATUS_SIGNED) {
                $this->mailerManager->sendProxyAndMandateSigned($proxy, $mandate);
            }
        }
    }

    /**
     * @param ProjectCgv $tos
     *
     * @return bool
     */
    public function createTOS(ProjectCgv $tos)
    {
        try {
            $pdfParameters = $this->getParameters($tos);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($tos->getId(), 'tos', $soapResult);

            return false;
        }

        $resultValue = $soapResult->value();
        $tos->setIdUniversign($resultValue['id']->scalarVal())
            ->setUrlUniversign($resultValue['url']->scalarVal())
            ->setStatus(ProjectCgv::STATUS_NO_SIGN);
        $this->entityManager->flush($tos);

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
            $this->notifyError($tos->id, 'tos', $soapResult);
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
     * @param WireTransferOutUniversign $universign
     *
     * @return bool
     */
    public function createWireTransferOutRequest(WireTransferOutUniversign $universign)
    {
        try {
            $pdfParameters = $this->getParameters($universign);
        } catch (\Exception $universignException) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($pdfParameters, "struct")]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($universign->getId(), 'wire_transfer_out', $soapResult);

            return false;
        }

        $resultValue = $soapResult->value();
        $universign->setIdUniversign($resultValue['id']->scalarVal())
                   ->setUrlUniversign($resultValue['url']->scalarVal());
        $this->entityManager->flush($universign);

        return true;
    }

    /**
     * @param UniversignEntityInterface $universign
     *
     * @return array
     * @throws \Exception
     */
    private function getParameters(UniversignEntityInterface $universign)
    {
        $documentName  = $universign->getName();
        $documentId    = $universign->getId();
        $signPositionX = 255;
        $signPositionY = 314;

        switch (get_class($universign)) {
            case ClientsMandats::class:
                /** @var ClientsMandats $universign */
                $client           = $universign->getIdClient();
                $routeName        = 'mandate_signature_status';
                $documentFullPath = $this->rootDir . '/../protected/pdf/mandat/' . $documentName;
                break;
            case ProjectsPouvoir::class:
                /** @var ProjectsPouvoir $universign */
                $clientId         = $universign->getIdProject()->getIdCompany()->getIdClientOwner();
                $client           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                $routeName        = 'proxy_signature_status';
                $documentFullPath = $this->rootDir . '/../protected/pdf/pouvoir/' . $documentName;
                break;
            case ProjectCgv::class:
                /** @var ProjectCgv $universign */
                $clientId         = $universign->getIdProject()->getIdCompany()->getIdClientOwner();
                $client           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                $routeName        = 'tos_signature_status';
                $documentFullPath = $this->rootDir . '/../protected/pdf/cgv_emprunteurs/' . $documentName;
                $signPositionX    = 430;
                $signPositionY    = 750;
                break;
            case WireTransferOutUniversign::class:
                /** @var WireTransferOutUniversign $universign */
                $client           = $universign->getIdWireTransferOut()->getClient();
                $routeName        = 'wire_transfer_out_signature_status';
                $documentFullPath = $this->rootDir . '/../protected/pdf/wire_transfer_out/' . $documentName;
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . get_class($universign) . '  id : ' . $documentId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . get_class($universign) . '  id : ' . $documentId);
        }

        $returnPage = [
            'success' => $this->router->generate($routeName, ['status' => 'success', 'documentId' => $documentId, 'clientHash' => $client->getHash()], 0),
            'fail'    => $this->router->generate($routeName, ['status' => 'fail', 'documentId' => $documentId, 'clientHash' => $client->getHash()], 0),
            'cancel'  => $this->router->generate($routeName, ['status' => 'cancel', 'documentId' => $documentId, 'clientHash' => $client->getHash()], 0)
        ];

        $docSignatureField = [
            'page'        => new Value(1, 'int'),
            'x'           => new Value($signPositionX, 'int'),
            'y'           => new Value($signPositionY, 'int'),
            'signerIndex' => new Value(0, 'int'),
            'label'       => new Value('Unilend', 'string')
        ];

        $signer = [
            'firstname'    => new Value($client->getPrenom(), 'string'),
            'lastname'     => new Value($client->getNom(), 'string'),
            'phoneNum'     => new Value(str_replace(' ', '', $client->getTelephone()), 'string'),
            'emailAddress' => new Value($client->getEmail(), 'string')
        ];

        $doc = [
            'content'         => new Value(file_get_contents($documentFullPath), 'base64'),
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
     * @param string              $documentId
     * @param string              $documentType
     * @param \PhpXmlRpc\Response $soapResult
     */
    private function notifyError($documentId, $documentType, $soapResult)
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
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

        $this->logger->error('Return Universign ' . $documentType . ' NOK (id: ' . $documentId . ') - Error code : ' . $soapResult->faultCode() . ' - Error Message : ' . $soapResult->faultString(),
            ['class' => __CLASS__, 'function' => __FUNCTION__]);
    }

    /**
     * @param \clients_mandats $activeMandate
     */
    private function updateDirectDebit(\clients_mandats $activeMandate)
    {
        /** @var \receptions $directDebit */
        $directDebit        = $this->entityManagerSimulator->getRepository('prelevements');
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
