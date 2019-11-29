<?php

declare(strict_types=1);

namespace Unilend\Service\ServiceTerms;

use League\Flysystem\FileNotFoundException;
use Swift_Attachment;
use Swift_Mailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Service\FileSystem\FileSystemHelper;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ServiceTermsNotificationSender
{
    private const MAIL_TYPE_SERVICE_TERMS_ACCEPTED = 'service-terms-accepted';

    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var ServiceTermsGenerator */
    private $serviceTermsGenerator;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var FileSystemHelper */
    private $fileSystemHelper;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param ServiceTermsGenerator   $serviceTermsGenerator
     * @param FileSystemHelper        $fileSystemHelper
     * @param Swift_Mailer            $mailer
     */
    public function __construct(
        TemplateMessageProvider $messageProvider,
        ServiceTermsGenerator $serviceTermsGenerator,
        FileSystemHelper $fileSystemHelper,
        Swift_Mailer $mailer
    ) {
        $this->messageProvider       = $messageProvider;
        $this->serviceTermsGenerator = $serviceTermsGenerator;
        $this->mailer                = $mailer;
        $this->fileSystemHelper      = $fileSystemHelper;
    }

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDoc
     *
     * @throws FileNotFoundException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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

        $pdf = $this->fileSystemHelper->getFileSystemForClass($acceptationsLegalDoc)->read(
            $this->serviceTermsGenerator->getFilePath($acceptationsLegalDoc)
        );
        $attachment = new Swift_Attachment(
            $pdf,
            'conditions-générales.pdf',
            'application/pdf'
        );
        $message->setTo($recipient->getEmail());
        $message->attach($attachment);

        return $this->mailer->send($message);
    }
}
