<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsMandats;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\WireTransferOutUniversign;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\FrontBundle\Controller\UniversignController;
use \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

class UniversignManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
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

    public function sign(UniversignEntityInterface $universign)
    {
        $this->updateSignature($universign);
        if (UniversignEntityInterface::STATUS_SIGNED === $universign->getStatus()) {
            switch (get_class($universign)) {
                case ProjectsPouvoir::class:
                    $this->signProxy($universign);
                    break;
                case ClientsMandats::class:
                    $this->signMandate($universign);
                    break;
                case ProjectCgv::class :
                    // nothing else to do;
                    break;
                case WireTransferOutUniversign::class:
                    $this->signWireTransferOut($universign);
                    break;
            }
        }
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
     * @param ProjectsPouvoir $proxy
     */
    private function signProxy(ProjectsPouvoir $proxy)
    {
        $mandate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findOneBy([
            'idProject' => $proxy->getIdProject(),
            'status'    => UniversignEntityInterface::STATUS_SIGNED
        ]);
        if ($mandate) {
            $this->mailerManager->sendProxyAndMandateSigned($proxy, $mandate);
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
                ->setStatus(UniversignEntityInterface::STATUS_PENDING)
                ->setBic($bankAccount->getBic())
                ->setIban($bankAccount->getIban());
        $this->entityManager->flush($mandate);

        return true;
    }

    /**
     * @param ClientsMandats $mandate
     */
    private function signMandate(ClientsMandats $mandate)
    {
        $this->updateSignature($mandate);

        $archivedMandate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findBy([
            'idProject' => $mandate->getIdProject(),
            'status'    => UniversignEntityInterface::STATUS_ARCHIVED
        ]);

        if (false === empty($archivedMandate)) {
            $futureDirectDebits = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements')->findBy([
                'idProject' => $mandate->getIdProject()->getIdProject(),
                'status'    => Prelevements::STATUS_PENDING
            ]);

            if (false === empty($futureDirectDebits)) {
                foreach ($futureDirectDebits as $futureDebit) {
                    $futureDebit->setIban($mandate->getIban())
                                ->setBic($mandate->getbic());
                }
                $this->entityManager->flush();
            }
        }

        $proxy = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->findOneBy([
            'idProject' => $mandate->getIdProject(),
            'status'    => UniversignEntityInterface::STATUS_SIGNED
        ]);
        if ($proxy) {
            $this->mailerManager->sendProxyAndMandateSigned($proxy, $mandate);
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
            ->setStatus(UniversignEntityInterface::STATUS_PENDING);
        $this->entityManager->flush($tos);

        return true;
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
     * @param WireTransferOutUniversign $wireTransferOutUniversign
     */
    private function signWireTransferOut(WireTransferOutUniversign $wireTransferOutUniversign)
    {
        $this->updateSignature($wireTransferOutUniversign);
        $wireTransferOut = $wireTransferOutUniversign->getIdWireTransferOut();
        if ($wireTransferOut) {
            switch ($wireTransferOutUniversign->getStatus()) {
                case UniversignEntityInterface::STATUS_CANCELED:
                    $wireTransferOut->setStatus(Virements::STATUS_CLIENT_DENIED);
                    break;
                case UniversignEntityInterface::STATUS_SIGNED:
                    $wireTransferOut->setStatus(Virements::STATUS_CLIENT_VALIDATED);
                    break;
                default:
                    //nothing
                    break;
            }
            $this->entityManager->flush($wireTransferOut);
        }
    }

    /**
     * @param UniversignEntityInterface $universign
     */
    private function updateSignature(UniversignEntityInterface $universign)
    {
        $soapClient            = new Client($this->universignURL);
        $signatureStatusResult = $soapClient->send(new Request('requester.getTransactionInfo', [new Value($universign->getIdUniversign(), "string")]));
        if ($signatureStatusResult->faultCode()) {
            $this->notifyError($universign->getId(), get_class($universign), $signatureStatusResult);
        } else {
            $status = $signatureStatusResult->value()['status']->scalarVal();
            switch ($status) {
                case UniversignEntityInterface::STATUS_LABEL_SIGNED :
                    $soapClient     = new Client($this->universignURL);
                    $documentResult = $soapClient->send(new Request('requester.getDocumentsByTransactionId', [new Value($universign->getIdUniversign(), "string")]));
                    if ($documentResult->faultCode()) {
                        $this->notifyError($universign->getId(), get_class($universign), $documentResult);
                    } else {
                        $resultValue     = $documentResult->value()[0];
                        $documentContent = $resultValue['content']->scalarVal();

                        file_put_contents($this->getDocumentFullPath($universign), $documentContent);
                        $universign->setStatus(UniversignEntityInterface::STATUS_SIGNED);
                    }
                    break;
                case UniversignEntityInterface::STATUS_LABEL_CANCELED :
                    $universign->setStatus(UniversignEntityInterface::STATUS_CANCELED);
                    break;
                case UniversignEntityInterface::STATUS_LABEL_PENDING :
                    //nothing to do
                    break;
                default :
                    $universign->setStatus(UniversignEntityInterface::STATUS_FAILED);
            }
            $this->entityManager->flush($universign);
        }
    }

    /**
     * @param UniversignEntityInterface $universign
     *
     * @return string
     * @throws \Exception
     */
    private function getDocumentFullPath(UniversignEntityInterface $universign)
    {
        $documentName = $universign->getName();
        $documentId   = $universign->getId();
        switch (get_class($universign)) {
            case ClientsMandats::class:
                /** @var ClientsMandats $universign */
                $documentFullPath = $this->rootDir . '/../protected/pdf/mandat/' . $documentName;
                break;
            case ProjectsPouvoir::class:
                /** @var ProjectsPouvoir $universign */
                $documentFullPath = $this->rootDir . '/../protected/pdf/pouvoir/' . $documentName;
                break;
            case ProjectCgv::class:
                /** @var ProjectCgv $universign */
                $documentFullPath = $this->rootDir . '/../protected/pdf/cgv_emprunteurs/' . $documentName;
                break;
            case WireTransferOutUniversign::class:
                /** @var WireTransferOutUniversign $universign */
                $documentFullPath = $this->rootDir . '/../protected/pdf/wire_transfer_out/' . $documentName;
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . get_class($universign) . '  id : ' . $documentId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . get_class($universign) . '  id : ' . $documentId);
        }

        return $documentFullPath;
    }

    /**
     * @param UniversignEntityInterface $universign
     *
     * @return array
     * @throws \Exception
     */
    private function getParameters(UniversignEntityInterface $universign)
    {
        $documentName     = $universign->getName();
        $documentId       = $universign->getId();
        $documentFullPath = $this->getDocumentFullPath($universign);
        $signPositionX    = 255;
        $signPositionY    = 314;

        switch (get_class($universign)) {
            case ClientsMandats::class:
                /** @var ClientsMandats $universign */
                $client    = $universign->getIdClient();
                $documentType = UniversignController::DOCUMENT_TYPE_MANDATE;
                break;
            case ProjectsPouvoir::class:
                /** @var ProjectsPouvoir $universign */
                $clientId  = $universign->getIdProject()->getIdCompany()->getIdClientOwner();
                $client    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                $documentType = UniversignController::DOCUMENT_TYPE_PROXY;
                break;
            case ProjectCgv::class:
                /** @var ProjectCgv $universign */
                $clientId      = $universign->getIdProject()->getIdCompany()->getIdClientOwner();
                $client        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                $documentType = UniversignController::DOCUMENT_TYPE_TERM_OF_USER;
                $signPositionX = 430;
                $signPositionY = 750;
                break;
            case WireTransferOutUniversign::class:
                /** @var WireTransferOutUniversign $universign */
                $clientId  = $universign->getIdWireTransferOut()->getProject()->getIdCompany()->getIdClientOwner();
                $client    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                $documentType = UniversignController::DOCUMENT_TYPE_WIRE_TRANSFER_OUT;
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . get_class($universign) . '  id : ' . $documentId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . get_class($universign) . '  id : ' . $documentId);
        }

        $returnPage = [
            'success' => $this->router->generate('universign_signature_status', ['documentType' => $documentType, 'documentId' => $documentId, 'clientHash' => $client->getHash()], 0),
            'fail'    => $this->router->generate('universign_signature_status', ['documentType' => $documentType, 'documentId' => $documentId, 'clientHash' => $client->getHash()], 0),
            'cancel'  => $this->router->generate('universign_signature_status', ['documentType' => $documentType, 'documentId' => $documentId, 'clientHash' => $client->getHash()], 0)
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
}
