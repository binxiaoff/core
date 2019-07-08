<?php

declare(strict_types=1);

namespace Unilend\Service\TermsOfSale;

use Swift_Attachment;
use Swift_Mailer;
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Service\Document\TermsOfSaleGenerator;
use Unilend\SwiftMailer\{TemplateMessageProvider, UnilendMailer};

class TermsOfSaleNotificationSender
{
    private const MAIL_TYPE_TERMS_OF_SALE_ACCEPTED = 'terms-of-sale-accepted';
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var RouterInterface */
    private $router;
    /** @var TermsOfSaleGenerator */
    private $termsOfSaleGenerator;
    /** @var Swift_Mailer */
    private $mailer;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param RouterInterface         $router
     * @param TermsOfSaleGenerator    $termsOfSaleGenerator
     * @param UnilendMailer           $mailer
     */
    public function __construct(TemplateMessageProvider $messageProvider, RouterInterface $router, TermsOfSaleGenerator $termsOfSaleGenerator, UnilendMailer $mailer)
    {
        $this->messageProvider      = $messageProvider;
        $this->router               = $router;
        $this->termsOfSaleGenerator = $termsOfSaleGenerator;
        $this->mailer               = $mailer;
    }

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDoc
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendAcceptedEmail(AcceptationsLegalDocs $acceptationsLegalDoc): int
    {
        $recipient = $acceptationsLegalDoc->getClient();

        if (empty($recipient->getEmail())) {
            return 0;
        }

        if (false === $this->termsOfSaleGenerator->exists($acceptationsLegalDoc)) {
            $this->termsOfSaleGenerator->generate($acceptationsLegalDoc);
        }

        $message = $this->messageProvider->newMessage(self::MAIL_TYPE_TERMS_OF_SALE_ACCEPTED, [
            'firstName' => $recipient->getFirstName(),
        ]);
        $message->setTo($recipient->getEmail());
        $message->attach(Swift_Attachment::fromPath($this->termsOfSaleGenerator->getPath($acceptationsLegalDoc)));

        return $this->mailer->send($message);
    }
}
