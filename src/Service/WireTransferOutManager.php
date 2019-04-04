<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\{Generator\UrlGeneratorInterface, RouterInterface};
use Unilend\Entity\{BankAccount, Projects, Settings, Users, Virements, Wallet, WalletType};
use Unilend\SwiftMailer\TemplateMessageProvider;

class WireTransferOutManager
{
    const TRANSFER_OUT_BY_PROJECT = 'project';
    const TRANSFER_OUT_BY_COMPANY = 'company';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var \NumberFormatter */
    private $currencyFormatter;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var RouterInterface */
    private $router;
    /** @var Packages */
    private $assetsPackages;
    /** @var OperationManager */
    private $operationManager;
    /** @var string */
    private $frontUrl;
    /** @var string */
    private $adminUrl;
    /** @var  LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface  $entityManager
     * @param ProjectManager          $projectManager
     * @param \NumberFormatter        $currencyFormatter
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param RouterInterface         $router
     * @param Packages                $assetsPackages
     * @param OperationManager        $operationManager
     * @param string                  $frontUrl
     * @param string                  $adminUrl
     * @param LoggerInterface         $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectManager $projectManager,
        \NumberFormatter $currencyFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        RouterInterface $router,
        Packages $assetsPackages,
        OperationManager $operationManager,
        $frontUrl,
        $adminUrl,
        LoggerInterface $logger
    )
    {
        $this->entityManager     = $entityManager;
        $this->projectManager    = $projectManager;
        $this->currencyFormatter = $currencyFormatter;
        $this->messageProvider   = $messageProvider;
        $this->mailer            = $mailer;
        $this->router            = $router;
        $this->assetsPackages    = $assetsPackages;
        $this->operationManager  = $operationManager;
        $this->frontUrl          = $frontUrl;
        $this->adminUrl          = $adminUrl;
        $this->logger            = $logger;
    }

    /**
     * @param Wallet        $wallet
     * @param float         $amount
     * @param BankAccount   $bankAccount
     * @param Projects|null $project
     * @param Users|null    $requestUser
     * @param null          $wireTransferPattern
     * @param \DateTime     $transferAt
     *
     * @return Virements
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createTransfer(
        Wallet $wallet,
        $amount,
        BankAccount $bankAccount = null,
        Projects $project = null,
        Users $requestUser = null,
        \DateTime $transferAt = null,
        $wireTransferPattern = null
    )
    {
        switch ($wallet->getIdType()->getLabel()) {
            case WalletType::LENDER:
                $type = Virements::TYPE_LENDER;
                break;
            case WalletType::BORROWER:
                $type = Virements::TYPE_BORROWER;
                break;
            case WalletType::UNILEND:
                $type = Virements::TYPE_UNILEND;
                break;
            case WalletType::DEBT_COLLECTOR:
                $type = Virements::TYPE_DEBT_COLLECTOR;
                break;
            default :
                throw new \InvalidArgumentException('Wallet type ' . $wallet->getIdType()->getLabel() . ' is not supported.');
        }
        if (null === $bankAccount && WalletType::UNILEND !== $wallet->getIdType()->getLabel()) {
            throw new \InvalidArgumentException('Bank account is not defined.');
        }
        $pattern = $wireTransferPattern ? $wireTransferPattern : $wallet->getWireTransferPattern();

        $wireTransferOut = new Virements();
        $wireTransferOut
            ->setClient($wallet->getIdClient())
            ->setProject($project)
            ->setMontant(bcmul($amount, 100))
            ->setMotif($pattern)
            ->setType($type)
            ->setBankAccount($bankAccount)
            ->setUserRequest($requestUser)
            ->setTransferAt($transferAt)
            ->setStatus(Virements::STATUS_PENDING);

        $this->entityManager->persist($wireTransferOut);

        switch ($wallet->getIdType()->getLabel()) {
            case WalletType::UNILEND:
            case WalletType::LENDER:
            case WalletType::DEBT_COLLECTOR:
                $this->validateTransfer($wireTransferOut);
                break;
            default :
                if ($bankAccount && $bankAccount->getIdClient() === $wallet->getIdClient()) {
                    $this->clientValidateTransfer($wireTransferOut);
                }
        }

        $this->entityManager->flush($wireTransferOut);

        if (Virements::STATUS_PENDING === $wireTransferOut->getStatus()) {
            $this->sendToValidateNotificationToClient($wireTransferOut);
        }

        return $wireTransferOut;
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function clientValidateTransfer(Virements $wireTransferOut)
    {
        $wireTransferOut->setStatus(Virements::STATUS_CLIENT_VALIDATED);
        $this->entityManager->flush($wireTransferOut);
        $this->sendToValidateNotificationToStaff($wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function clientDeniedTransfer(Virements $wireTransferOut)
    {
        $wireTransferOut->setStatus(Virements::STATUS_CLIENT_DENIED);
        $this->entityManager->flush($wireTransferOut);
    }

    /**
     * @param Virements  $wireTransferOut
     * @param Users|null $validationUser
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function validateTransfer(Virements $wireTransferOut, Users $validationUser = null)
    {
        $wireTransferOut
            ->setStatus(Virements::STATUS_VALIDATED)
            ->setUserValidation($validationUser)
            ->setValidated(new \DateTime());

        $this->entityManager->flush($wireTransferOut);

        $this->operationManager->withdraw($wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     */
    public function sendToValidateNotificationToClient(Virements $wireTransferOut)
    {
        if ($wireTransferOut->getProject() && Virements::TYPE_BORROWER === $wireTransferOut->getType()) {
            $restFunds   = $this->projectManager->getRestOfFundsToRelease($wireTransferOut->getProject(), false);
            $bankAccount = $wireTransferOut->getBankAccount();

            if ($bankAccount) {
                $universignLink = $this->router->generate('wire_transfer_out_request_pdf', [
                    'clientHash'        => $wireTransferOut->getClient()->getHash(),
                    'wireTransferOutId' => $wireTransferOut->getIdVirement()
                ], UrlGeneratorInterface::ABSOLUTE_PATH);

                $keywords = [
                    'firstName'       => $wireTransferOut->getClient()->getPrenom(),
                    'remainingFunds'  => $this->currencyFormatter->formatCurrency($restFunds, 'EUR'),
                    'amount'          => $this->currencyFormatter->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                    'iban'            => chunk_split($bankAccount->getIban(), 4, ' '),
                    'bankAccountName' => $bankAccount->getIdClient()->getPrenom() . ' ' . $bankAccount->getIdClient()->getNom(),
                    'universignLink'  => $this->frontUrl . $universignLink
                ];

                /** @var \Unilend\SwiftMailer\TemplateMessage $message */
                $message = $this->messageProvider->newMessage('wire-transfer-out-borrower-notification', $keywords);
                try {
                    $message->setTo($wireTransferOut->getClient()->getEmail());
                    $this->mailer->send($message);
                } catch (\Exception $exception) {
                    $this->logger->error('Could not send email : wire-transfer-out-borrower-notification - Exception: ' . $exception->getMessage(), [
                        'id_mail_template' => $message->getTemplateId(),
                        'id_client'        => $wireTransferOut->getClient()->getIdClient(),
                        'class'            => __CLASS__,
                        'function'         => __FUNCTION__,
                        'exceptionFile'    => $exception->getFile(),
                        'exceptionLine'    => $exception->getLine()
                    ]);
                }
            }
        }
    }

    /**
     * @param Virements $wireTransferOut
     */
    private function sendToValidateNotificationToStaff(Virements $wireTransferOut)
    {
        if ($wireTransferOut->getProject() && Virements::TYPE_BORROWER === $wireTransferOut->getType()) {

            $keywords = [
                'amount'  => $this->currencyFormatter->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                'project' => $wireTransferOut->getProject()->getTitle(),
                'url'     => $this->adminUrl
            ];

            $settings = $this->entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Adresse controle interne']);

            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
            $message = $this->messageProvider->newMessage('wire-transfer-out-to-validate-staff-notification', $keywords);
            try {
                $message->setTo($settings->getValue());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->logger->error('Could not send email : wire-transfer-out-to-validate-staff-notification - Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'email_address'    => $settings->getValue(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'exceptionFile'    => $exception->getFile(),
                    'exceptionLine'    => $exception->getLine()
                ]);
            }
        }
    }

    /**
     * @param Wallet $wallet
     *
     * @return float
     */
    public function getCommittedAmount(Wallet $wallet): float
    {
        $amount = 0;

        $wireTransferOuts = $this->entityManager->getRepository(Virements::class)->findBy([
            'idClient' => $wallet->getIdClient(),
            'status'   => [Virements::STATUS_PENDING, Virements::STATUS_CLIENT_VALIDATED]
        ]);

        foreach ($wireTransferOuts as $wireTransferOut) {
            $amount = round(bcadd($amount, bcdiv($wireTransferOut->getMontant(), 100, 4), 4), 2);
        }

        return $amount;
    }
}
