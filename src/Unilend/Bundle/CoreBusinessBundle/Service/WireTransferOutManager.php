<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
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

    /** @var string */
    private $frontUrl;

    public function __construct(
        EntityManager $entityManager,
        ProjectManager $projectManager,
        \NumberFormatter $currencyFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        RouterInterface $router,
        Packages $assetsPackages,
        $frontUrl
    ) {
        $this->entityManager     = $entityManager;
        $this->projectManager    = $projectManager;
        $this->currencyFormatter = $currencyFormatter;
        $this->messageProvider   = $messageProvider;
        $this->mailer            = $mailer;
        $this->router            = $router;
        $this->assetsPackages    = $assetsPackages;
        $this->frontUrl          = $frontUrl;

    }

    public function sendWireTransferOutNotificationToBorrower(Virements $wireTransferOut)
    {
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
                'lien_tw'           => $twitter->getValue()
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->messageProvider->newMessage('wire-transfer-out-borrower-notification', $varMail);
            $message->setTo($wireTransferOut->getClient()->getEmail());
            $this->mailer->send($message);
        }
    }
}
