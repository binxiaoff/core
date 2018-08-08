<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsMandats;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectBeneficialOwnerUniversign;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WireTransferOutUniversign;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\SlackManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WireTransferOutManager;
use Unilend\Bundle\FrontBundle\Controller\UniversignController;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class UniversignManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var Router */
    private $router;
    /** @var LoggerInterface */
    private $logger;
    /** @var TranslatorInterface */
    private $translator;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var WireTransferOutManager */
    private $wireTransferOutManager;
    /** @var string */
    private $universignURL;
    /** @var string */
    private $rootDir;
    /** @var SlackManager */
    private $slackManager;

    /**
     * @param EntityManager           $entityManager
     * @param MailerManager           $mailerManager
     * @param RouterInterface         $router
     * @param LoggerInterface         $logger
     * @param TranslatorInterface     $translator
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param WireTransferOutManager  $wireTransferOutManager
     * @param SlackManager            $slackManager
     * @param string                  $universignURL
     * @param string                  $rootDir
     */
    public function __construct(
        EntityManager $entityManager,
        MailerManager $mailerManager,
        RouterInterface $router,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        WireTransferOutManager $wireTransferOutManager,
        SlackManager $slackManager,
        $universignURL,
        $rootDir
    )
    {
        $this->entityManager          = $entityManager;
        $this->mailerManager          = $mailerManager;
        $this->router                 = $router;
        $this->logger                 = $logger;
        $this->translator             = $translator;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->wireTransferOutManager = $wireTransferOutManager;
        $this->slackManager           = $slackManager;
        $this->universignURL          = $universignURL;
        $this->rootDir                = $rootDir;
    }

    /**
     * @param UniversignEntityInterface $document
     */
    public function sign(UniversignEntityInterface $document)
    {
        $this->updateSignature($document);

        if (UniversignEntityInterface::STATUS_SIGNED === $document->getStatus()) {
            switch (get_class($document)) {
                case ProjectsPouvoir::class:
                    /** @var ProjectsPouvoir $document */
                    $this->signProxy($document);
                    break;
                case ClientsMandats::class:
                    /** @var ClientsMandats $document */
                    $this->signMandate($document);
                    break;
                case ProjectCgv::class:
                    /** @var ProjectCgv $document */
                    $this->signTermsOfSale($document);
                    break;
                case WireTransferOutUniversign::class:
                    /** @var WireTransferOutUniversign $document */
                    $this->signWireTransferOut($document);
                    break;
                case ProjectBeneficialOwnerUniversign::class:
                    /** @var ProjectBeneficialOwnerUniversign $document */
                    $this->signBeneficialOwnerDeclaration($document);
                    break;
            }
        }
    }

    /**
     * @param string $signatureType
     * @param int    $signatureId
     * @param array  $documents
     *
     * @return bool|Value
     */
    private function createSignature($signatureType, $signatureId, array $documents)
    {
        try {
            $parameters = $this->getSignatureParameters($signatureType, $signatureId, $documents);
        } catch (\Exception $exception) {
            return false;
        }

        $soapClient  = new Client($this->universignURL);
        $soapRequest = new Request('requester.requestTransaction', [new Value($parameters, 'struct')]);
        $soapResult  = $soapClient->send($soapRequest);

        if ($soapResult->faultCode()) {
            $this->notifyError($signatureType, $signatureId, $soapResult);

            return false;
        }

        return $soapResult->value();
    }

    /**
     * @param ProjectsPouvoir $proxy
     *
     * @return bool
     */
    public function createProxy(ProjectsPouvoir $proxy)
    {
        $resultValue = $this->createSignature(ProjectsPouvoir::DOCUMENT_TYPE, $proxy->getId(), [$proxy]);

        if ($resultValue instanceof Value) {
            $proxy
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal())
                ->setStatus(ProjectsPouvoir::STATUS_PENDING);
            $this->entityManager->flush($proxy);

            return true;
        }

        return false;
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
     * @param Projects                              $project
     * @param ProjectsPouvoir                       $proxy
     * @param ClientsMandats                        $mandate
     * @param ProjectBeneficialOwnerUniversign|null $beneficialOwnerUniversign
     *
     * @return bool
     */
    public function createProject(Projects $project, ProjectsPouvoir $proxy, ClientsMandats $mandate, ProjectBeneficialOwnerUniversign $beneficialOwnerUniversign = null)
    {
        $bankAccount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($project->getIdCompany()->getIdClientOwner());
        if (null === $bankAccount) {
            $this->logger->warning('No validated bank account found for mandate ' . $mandate->getId() . ' of client ' . $mandate->getIdClient()->getIdClient(), ['function' => __FUNCTION__]);

            return false;
        }

        $documents = [$proxy, $mandate];
        if (null !== $beneficialOwnerUniversign) {
            $documents[] = $beneficialOwnerUniversign;
        }

        $resultValue = $this->createSignature(UniversignController::SIGNATURE_TYPE_PROJECT, $project->getIdProject(), $documents);

        if ($resultValue instanceof Value) {
            $proxy
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal())
                ->setStatus(ProjectsPouvoir::STATUS_PENDING);

            $mandate
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal())
                ->setStatus(UniversignEntityInterface::STATUS_PENDING)
                ->setBic($bankAccount->getBic())
                ->setIban($bankAccount->getIban());

            if (null !== $beneficialOwnerUniversign) {
                $beneficialOwnerUniversign
                    ->setIdUniversign($resultValue['id']->scalarVal())
                    ->setUrlUniversign($resultValue['url']->scalarVal())
                    ->setStatus(ProjectsPouvoir::STATUS_PENDING);
            }

            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    /**
     * @param ClientsMandats $mandate
     *
     * @return bool
     */
    public function createMandate(ClientsMandats $mandate)
    {
        $bankAccount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($mandate->getIdClient());
        if (null === $bankAccount) {
            $this->logger->warning('No validated bank account found for mandate ' . $mandate->getId() . ' of client ' . $mandate->getIdClient()->getIdClient(), ['function' => __FUNCTION__]);

            return false;
        }

        $resultValue = $this->createSignature(ClientsMandats::DOCUMENT_TYPE, $mandate->getId(), [$mandate]);

        if ($resultValue instanceof Value) {
            $mandate
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal())
                ->setStatus(UniversignEntityInterface::STATUS_PENDING)
                ->setBic($bankAccount->getBic())
                ->setIban($bankAccount->getIban());
            $this->entityManager->flush($mandate);

            return true;
        }

        return false;
    }

    /**
     * @param ClientsMandats $mandate
     */
    private function signMandate(ClientsMandats $mandate)
    {
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
                    $futureDebit
                        ->setIban($mandate->getIban())
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
        $resultValue = $this->createSignature(ProjectCgv::DOCUMENT_TYPE, $tos->getId(), [$tos]);

        if ($resultValue instanceof Value) {
            $tos
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal())
                ->setStatus(UniversignEntityInterface::STATUS_PENDING);
            $this->entityManager->flush($tos);

            return true;
        }

        return false;
    }

    /**
     * @param WireTransferOutUniversign $wireTransferOutUniversign
     *
     * @return bool
     */
    public function createWireTransferOutRequest(WireTransferOutUniversign $wireTransferOutUniversign)
    {
        $resultValue = $this->createSignature(WireTransferOutUniversign::DOCUMENT_TYPE, $wireTransferOutUniversign->getId(), [$wireTransferOutUniversign]);

        if ($resultValue instanceof Value) {
            $wireTransferOutUniversign
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal());
            $this->entityManager->flush($wireTransferOutUniversign);

            return true;
        }

        return false;
    }

    /**
     * @param WireTransferOutUniversign $wireTransferOutUniversign
     */
    private function signWireTransferOut(WireTransferOutUniversign $wireTransferOutUniversign)
    {
        $wireTransferOut = $wireTransferOutUniversign->getIdWireTransferOut();

        if ($wireTransferOut) {
            switch ($wireTransferOutUniversign->getStatus()) {
                case UniversignEntityInterface::STATUS_CANCELED:
                    $this->wireTransferOutManager->clientDeniedTransfer($wireTransferOut);
                    break;
                case UniversignEntityInterface::STATUS_SIGNED:
                    $this->wireTransferOutManager->clientValidateTransfer($wireTransferOut);
                    break;
                default:
                    //nothing
                    break;
            }
        }
    }

    /**
     * @param ProjectBeneficialOwnerUniversign $beneficialOwnerDeclaration
     *
     * @return bool
     */
    public function createBeneficialOwnerDeclaration(ProjectBeneficialOwnerUniversign $beneficialOwnerDeclaration)
    {
        $resultValue = $this->createSignature(ProjectBeneficialOwnerUniversign::DOCUMENT_TYPE, $beneficialOwnerDeclaration->getId(), [$beneficialOwnerDeclaration]);

        if ($resultValue instanceof Value) {
            $beneficialOwnerDeclaration
                ->setIdUniversign($resultValue['id']->scalarVal())
                ->setUrlUniversign($resultValue['url']->scalarVal());
            $this->entityManager->flush($beneficialOwnerDeclaration);

            return true;
        }

        return false;
    }

    /**
     * @param ProjectBeneficialOwnerUniversign $projectDeclaration
     */
    private function signBeneficialOwnerDeclaration(ProjectBeneficialOwnerUniversign $projectDeclaration)
    {
        $projectDeclaration->getIdDeclaration()->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_VALIDATED);

        $this->entityManager->flush($projectDeclaration->getIdDeclaration());
    }

    /**
     * @param UniversignEntityInterface $document
     */
    private function updateSignature(UniversignEntityInterface $document)
    {
        $soapClient            = new Client($this->universignURL);
        $signatureStatusResult = $soapClient->send(new Request('requester.getTransactionInfo', [new Value($document->getIdUniversign(), 'string')]));

        if ($signatureStatusResult->faultCode()) {
            $this->notifyError(get_class($document), $document->getId(), $signatureStatusResult);
        } else {
            $status = $signatureStatusResult->value()['status']->scalarVal();

            switch ($status) {
                case UniversignEntityInterface::STATUS_LABEL_SIGNED:
                    $soapClient     = new Client($this->universignURL);
                    $documentResult = $soapClient->send(new Request('requester.getDocumentsByTransactionId', [new Value($document->getIdUniversign(), 'string')]));

                    if ($documentResult->faultCode()) {
                        $this->notifyError(get_class($document), $document->getId(), $documentResult);
                    } else {
                        $documentType = $this->getDocumentType($document);

                        foreach ($documentResult->value() as $signedDocument) {
                            if (
                                isset($signedDocument['content'], $signedDocument['metaData'])
                                && $signedDocument['content'] instanceof Value
                                && $signedDocument['metaData'] instanceof Value
                                && isset($signedDocument['metaData']->scalarVal()['docType'])
                                && $signedDocument['metaData']->scalarVal()['docType'] instanceof Value
                                && $signedDocument['metaData']->scalarVal()['docId'] instanceof Value
                                && $documentType === $signedDocument['metaData']->scalarVal()['docType']->scalarVal()
                                && $document->getId() === $signedDocument['metaData']->scalarVal()['docId']->scalarVal()
                            ) {
                                file_put_contents($this->getDocumentFullPath($document), $signedDocument['content']->scalarVal());
                                $document->setStatus(UniversignEntityInterface::STATUS_SIGNED);
                                break;
                            }
                        }
                    }
                    break;
                case UniversignEntityInterface::STATUS_LABEL_CANCELED:
                    $document->setStatus(UniversignEntityInterface::STATUS_CANCELED);
                    break;
                case UniversignEntityInterface::STATUS_LABEL_PENDING:
                    //nothing to do
                    break;
                default :
                    $document->setStatus(UniversignEntityInterface::STATUS_FAILED);
                    break;
            }
            $this->entityManager->flush($document);
        }
    }

    /**
     * @param UniversignEntityInterface $document
     *
     * @return string
     * @throws \Exception
     */
    private function getDocumentFullPath(UniversignEntityInterface $document)
    {
        $documentName = $document->getName();
        $documentId   = $document->getId();

        switch (get_class($document)) {
            case ClientsMandats::class:
                $documentFullPath = $this->rootDir . '/../protected/pdf/mandat/' . $documentName;
                break;
            case ProjectsPouvoir::class:
                $documentFullPath = $this->rootDir . '/../protected/pdf/pouvoir/' . $documentName;
                break;
            case ProjectCgv::class:
                $documentFullPath = $this->rootDir . '/../' . ProjectCgv::BASE_PATH . $documentName;
                break;
            case WireTransferOutUniversign::class:
                $documentFullPath = $this->rootDir . '/../protected/pdf/wire_transfer_out/' . $documentName;
                break;
            case ProjectBeneficialOwnerUniversign::class:
                $documentFullPath = $this->rootDir . '/../protected/pdf/beneficial_owner/' . $documentName;
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . get_class($document) . '  id : ' . $documentId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . get_class($document) . '  id : ' . $documentId);
        }

        return $documentFullPath;
    }

    /**
     * @param UniversignEntityInterface $document
     *
     * @return string
     * @throws \Exception
     */
    private function getDocumentType(UniversignEntityInterface $document)
    {
        $reflectionObject = new \ReflectionObject($document);
        $type             = $reflectionObject->getConstant('DOCUMENT_TYPE');

        if (false === $type) {
            $this->logger->error('Unknown Universign document type : ' . get_class($document) . '  id : ' . $document->getId(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
            throw new \Exception('Unknown Universign document type : ' . get_class($document) . '  id : ' . $document->getId());
        }

        return $type;
    }

    /**
     * @param UniversignEntityInterface $document
     *
     * @return Clients
     * @throws \Exception
     */
    private function getDocumentClient(UniversignEntityInterface $document)
    {
        switch (get_class($document)) {
            case ClientsMandats::class:
                /** @var ClientsMandats $document */
                $client = $document->getIdClient();
                break;
            case ProjectsPouvoir::class:
                /** @var ProjectsPouvoir $document */
                $clientId = $document->getIdProject()->getIdCompany()->getIdClientOwner();
                $client   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                break;
            case ProjectCgv::class:
                /** @var ProjectCgv $document */
                $clientId = $document->getIdProject()->getIdCompany()->getIdClientOwner();
                $client   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                break;
            case WireTransferOutUniversign::class:
                /** @var WireTransferOutUniversign $document */
                $clientId = $document->getIdWireTransferOut()->getProject()->getIdCompany()->getIdClientOwner();
                $client   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                break;
            case ProjectBeneficialOwnerUniversign::class:
                /** @var ProjectBeneficialOwnerUniversign $document */
                $clientId = $document->getIdProject()->getIdCompany()->getIdClientOwner();
                $client   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . get_class($document) . '  id : ' . $document->getId(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . get_class($document) . '  id : ' . $document->getId());
        }

        return $client;
    }

    /**
     * @param string                      $signatureType
     * @param int                         $signatureId
     * @param UniversignEntityInterface[] $documents
     *
     * @return array
     * @throws \Exception
     */
    private function getSignatureParameters($signatureType, $signatureId, array $documents)
    {
        $docs            = [];
        $signatureClient = null;

        if (empty($documents)) {
            $this->logger->error('No document for signature : ' . $signatureType . ' - id : ' . $signatureId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
            throw new \Exception('No document for signature : ' . $signatureType . ' - id : ' . $signatureId);
        }

        foreach ($documents as $document) {
            $client = $this->getDocumentClient($document);

            if (null === $signatureClient) {
                $signatureClient = $client;
            }

            if ($signatureClient !== $client) {
                $this->logger->error('Document client does not match others : ' . $signatureType . '  id : ' . $signatureId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Document client does not match others : ' . $signatureType . '  id : ' . $signatureId);
            }

            $docs[] = new Value($this->getDocumentParameters($document), 'struct');
        }

        $returnPage = [
            'success' => $this->router->generate('universign_signature_status', ['signatureType' => $signatureType, 'signatureId' => $signatureId, 'clientHash' => $client->getHash()], 0),
            'fail'    => $this->router->generate('universign_signature_status', ['signatureType' => $signatureType, 'signatureId' => $signatureId, 'clientHash' => $client->getHash()], 0),
            'cancel'  => $this->router->generate('universign_signature_status', ['signatureType' => $signatureType, 'signatureId' => $signatureId, 'clientHash' => $client->getHash()], 0)
        ];

        $signer = [
            'firstname'    => new Value($client->getPrenom(), 'string'),
            'lastname'     => new Value($client->getNom(), 'string'),
            'phoneNum'     => new Value(str_replace(' ', '', $client->getTelephone()), 'string'),
            'emailAddress' => new Value($client->getEmail(), 'string')
        ];

        return [
            'documents'          => new Value($docs, 'array'),
            'signers'            => new Value([new Value($signer, 'struct')], 'array'),
            'successURL'         => new Value($returnPage['success'], 'string'),
            'failURL'            => new Value($returnPage['fail'], 'string'),
            'cancelURL'          => new Value($returnPage['cancel'], 'string'),
            'certificateType'    => new Value('simple', 'string'),
            'language'           => new Value('fr', 'string'),
            'identificationType' => new Value('sms', 'string'),
            'description'        => new Value('Signature type : ' . $signatureType . ' - id : ' . $signatureId, 'string')
        ];
    }

    /**
     * @param UniversignEntityInterface $document
     *
     * @return array
     * @throws \Exception
     */
    private function getDocumentParameters(UniversignEntityInterface $document)
    {
        $documentId       = $document->getId();
        $documentType     = $this->getDocumentType($document);
        $documentFullPath = $this->getDocumentFullPath($document);
        $documentName     = $this->translator->trans('universign_document-name-' . $this->getDocumentType($document));
        $signPositionX    = 255;
        $signPositionY    = 314;

        switch (get_class($document)) {
            case ProjectCgv::class:
                $signPositionX = 430;
                $signPositionY = 750;
                break;
            case ClientsMandats::class:
            case ProjectsPouvoir::class:
            case WireTransferOutUniversign::class:
            case ProjectBeneficialOwnerUniversign::class:
                break;
            default:
                $this->logger->error('Unknown Universign document type : ' . get_class($document) . '  id : ' . $documentId, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                throw new \Exception('Unknown Universign document type : ' . get_class($document) . '  id : ' . $documentId);
        }

        $docSignatureField = [
            'page'        => new Value(1, 'int'),
            'x'           => new Value($signPositionX, 'int'),
            'y'           => new Value($signPositionY, 'int'),
            'signerIndex' => new Value(0, 'int'),
            'label'       => new Value($documentName, 'string')
        ];

        $metaData = [
            'docType' => new Value($documentType, 'string'),
            'docId'   => new Value($documentId, 'int')
        ];

        return [
            'content'         => new Value(file_get_contents($documentFullPath), 'base64'),
            'name'            => new Value($documentName, 'string'),
            'signatureFields' => new Value([new Value($docSignatureField, 'struct')], 'array'),
            'metaData'        => new Value($metaData, 'struct')
        ];
    }

    /**
     * @param string              $signatureType
     * @param int                 $signatureId
     * @param \PhpXmlRpc\Response $soapResult
     */
    private function notifyError($signatureType, $signatureId, $soapResult)
    {
        $setting  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'DebugMailIt']);
        $keywords = [
            '[DOCUMENT_TYPE]'     => $signatureType,
            '[DOCUMENT_ID]'       => $signatureId,
            '[SOAP_ERROR_CODE]'   => $soapResult->faultCode(),
            '[SOAP_ERROR_REASON]' => $soapResult->faultString()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-erreur-universign', $keywords, false);

        try {
            $message->setTo($setting->getValue());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email : notification-erreur-universign - Exception: ' . $exception->getMessage(),
                [ 'email address' => $setting->getValue(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }

        $this->logger->error(
            'Return Universign ' . $signatureType . ' NOK (id: ' . $signatureId . ') - Error code : ' . $soapResult->faultCode() . ' - Error Message : ' . $soapResult->faultString(),
            ['class' => __CLASS__, 'function' => __FUNCTION__]
        );
    }

    /**
     * @param Projects $project
     */
    public function cancelProxyAndMandate(Projects $project)
    {
        try {
            $mandate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->findOneBy(['idProject' => $project]);
            $proxy   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->findOneBy(['idProject' => $project]);
            $client  = new Client($this->universignURL);

            if (null !== $mandate) {
                $mandate->setStatus(UniversignEntityInterface::STATUS_CANCELED);
                $this->entityManager->flush($mandate);

                $request          = new Request('requester.cancelTransaction', [new Value($mandate->getIdUniversign(), "string")]);
                $universignReturn = $client->send($request);

                if ($universignReturn->faultCode()) {
                    $this->logger->error('Mandate cancellation failed. Reason : ' . $universignReturn->faultString() . ' (project ' . $mandate->getIdProject()->getIdProject() . ')', [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_project' => $mandate->getIdProject()->getIdProject()
                    ]);
                }
            }

            if (null !== $proxy) {
                $proxy->setStatus(UniversignEntityInterface::STATUS_CANCELED);
                $this->entityManager->flush($proxy);

                $request          = new Request('requester.cancelTransaction', [new Value($proxy->getIdUniversign(), "string")]);
                $universignReturn = $client->send($request);

                if ($universignReturn->faultCode()) {
                    $this->logger->error('Proxy cancellation failed. Reason : ' . $universignReturn->faultString() . ' (project ' . $proxy->getIdProject()->getIdProject() . ')', [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_project' => $proxy->getIdProject()->getIdProject()
                    ]);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->critical('An exception occurred while cancelling mandate and proxy for project: ' . $project->getIdProject() .
                ' - Exception: ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }

    /**
     * @param ProjectCgv $termsOfSale
     */
    public function signTermsOfSale(ProjectCgv $termsOfSale): void
    {
        if ($termsOfSale->getIdProject() && $termsOfSale->getIdProject()->getIdCommercial() && false === empty($termsOfSale->getIdProject()->getIdCommercial()->getSlack())) {
            $message = $this->slackManager->getProjectName($termsOfSale->getIdProject()) . ' : les CGV emprunteurs sont signées.';
            $this->slackManager->sendMessage($message, '@' . $termsOfSale->getIdProject()->getIdCommercial()->getSlack());
        }
    }
}
