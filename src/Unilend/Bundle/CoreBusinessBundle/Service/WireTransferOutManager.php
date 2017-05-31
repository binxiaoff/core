<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class WireTransferOutManager
{
    /** @var EntityManager */
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

    /**
     * WireTransferOutManager constructor.
     *
     * @param EntityManager           $entityManager
     * @param ProjectManager          $projectManager
     * @param \NumberFormatter        $currencyFormatter
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param RouterInterface         $router
     * @param Packages                $assetsPackages
     * @param OperationManager        $operationManager
     * @param string                  $frontUrl
     * @param string                  $adminUrl
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectManager $projectManager,
        \NumberFormatter $currencyFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        RouterInterface $router,
        Packages $assetsPackages,
        OperationManager $operationManager,
        $frontUrl,
        $adminUrl
    ) {
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
     */
    public function createTransfer(
        Wallet $wallet,
        $amount,
        BankAccount $bankAccount = null,
        Projects $project = null,
        Users $requestUser = null,
        \DateTime $transferAt = null,
        $wireTransferPattern = null
    ) {
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
            default :
                throw new \InvalidArgumentException('Wallet type ' . $wallet->getIdType()->getLabel() . ' is not supported.');
        }
        if (null === $bankAccount && WalletType::UNILEND !== $wallet->getIdType()->getLabel()) {
            throw new \InvalidArgumentException('Bank account is not defined.');
        }
        $pattern = $wireTransferPattern ? $wireTransferPattern : $wallet->getWireTransferPattern();

        $wireTransferOut = new Virements();
        $wireTransferOut->setClient($wallet->getIdClient())
                        ->setProject($project)
                        ->setMontant(bcmul($amount, 100))
                        ->setMotif($pattern)
                        ->setType($type)
                        ->setBankAccount($bankAccount)
                        ->setUserRequest($requestUser)
                        ->setTransferAt($transferAt)
                        ->setStatus(Virements::STATUS_PENDING);

        $this->entityManager->persist($wireTransferOut);

        if (WalletType::UNILEND === $wallet->getIdType()->getLabel() || WalletType::LENDER === $wallet->getIdType()->getLabel()) {
            $this->validateTransfer($wireTransferOut);
        } else {
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
     */
    public function clientValidateTransfer(Virements $wireTransferOut)
    {
        $wireTransferOut->setStatus(Virements::STATUS_CLIENT_VALIDATED);
        $this->entityManager->flush($wireTransferOut);
        $this->sendToValidateNotificationToStaff($wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     */
    public function clientDeniedTransfer(Virements $wireTransferOut)
    {
        $wireTransferOut->setStatus(Virements::STATUS_CLIENT_DENIED);
        $this->entityManager->flush($wireTransferOut);
    }

    /**
     * @param Virements  $wireTransferOut
     * @param Users|null $validationUser
     */
    public function validateTransfer(Virements $wireTransferOut, Users $validationUser = null)
    {
        $wireTransferOut->setStatus(Virements::STATUS_VALIDATED)
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
                $facebook       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook']);
                $twitter        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter']);
                $universignLink = $this->router->generate(
                    'wire_transfer_out_request_pdf',
                    ['clientHash' => $wireTransferOut->getClient()->getHash(), 'wireTransferOutId' => $wireTransferOut->getIdVirement()],
                    UrlGeneratorInterface::ABSOLUTE_PATH
                );

                $varMail = array(
                    'surl'              => $this->assetsPackages->getUrl(''),
                    'url'               => $this->frontUrl,
                    'first_name'        => $wireTransferOut->getClient()->getPrenom(),
                    'rest_funds'        => $this->currencyFormatter->formatCurrency($restFunds, 'EUR'),
                    'amount'            => $this->currencyFormatter->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                    'iban'              => chunk_split($bankAccount->getIban(), 4, ' '),
                    'bank_account_name' => $bankAccount->getIdClient()->getPrenom() . ' ' . $bankAccount->getIdClient()->getNom(),
                    'universign_link'   => $this->frontUrl . $universignLink,
                    'lien_fb'           => $facebook->getValue(),
                    'lien_tw'           => $twitter->getValue(),
                    'annee'             => date('Y')
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->messageProvider->newMessage('wire-transfer-out-borrower-notification', $varMail);
                $message->setTo($wireTransferOut->getClient()->getEmail());
                $this->mailer->send($message);
            }
        }
    }

    /**
     * @param Virements $wireTransferOut
     */
    private function sendToValidateNotificationToStaff(Virements $wireTransferOut)
    {
        if ($wireTransferOut->getProject() && Virements::TYPE_BORROWER === $wireTransferOut->getType()) {

            $varMail = array(
                'amount'  => $this->currencyFormatter->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                'project' => $wireTransferOut->getProject()->getTitle(),
                'url'     => $this->adminUrl
            );

            $settings = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse controle interne']);

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->messageProvider->newMessage('wire-transfer-out-to-validate-staff-notification', $varMail);
            $message->setTo($settings->getValue());
            $this->mailer->send($message);

        }
    }
}
