<?php

declare(strict_types=1);

namespace Unilend\Service\ServiceTerms;

use Swift_Attachment;
use Swift_Mailer;
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\SwiftMailer\{TemplateMessageProvider, UnilendMailer};

class ServiceTermsNotificationSender
{
    private const MAIL_TYPE_SERVICE_TERMS_ACCEPTED = 'service-terms-accepted';

    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var RouterInterface */
    private $router;
    /** @var ServiceTermsGenerator */
    private $serviceTermsGenerator;
    /** @var Swift_Mailer */
    private $mailer;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param RouterInterface         $router
     * @param ServiceTermsGenerator   $serviceTermsGenerator
     * @param UnilendMailer           $mailer
     */
    public function __construct(TemplateMessageProvider $messageProvider, RouterInterface $router, ServiceTermsGenerator $serviceTermsGenerator, UnilendMailer $mailer)
    {
        $this->messageProvider       = $messageProvider;
        $this->router                = $router;
        $this->serviceTermsGenerator = $serviceTermsGenerator;
        $this->mailer                = $mailer;
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

        $this->serviceTermsGenerator->generate($acceptationsLegalDoc);

        $message = $this->messageProvider->newMessage(self::MAIL_TYPE_SERVICE_TERMS_ACCEPTED, [
            'firstName' => $recipient->getFirstName(),
        ]);
        $message->setTo($recipient->getEmail());
        $message->attach(Swift_Attachment::fromPath($this->serviceTermsGenerator->getFilePath($acceptationsLegalDoc)));

        return $this->mailer->send($message);
    }
}
