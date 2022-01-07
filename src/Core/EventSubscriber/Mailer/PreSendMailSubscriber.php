<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\Mailer;

use Doctrine\ORM\ORMException;
use KLS\Core\Entity\MailLog;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Repository\MailLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Message;

class PreSendMailSubscriber implements EventSubscriberInterface
{
    private bool              $enableErrorDelivery;
    private ?string           $errorReportingEmail;
    private MailLogRepository $mailLogRepository;
    private LoggerInterface   $logger;

    public function __construct(
        bool $enableErrorDelivery,
        ?string $errorReportingEmail,
        MailLogRepository $mailLogRepository,
        LoggerInterface $logger
    ) {
        $this->enableErrorDelivery = $enableErrorDelivery;
        $this->errorReportingEmail = $errorReportingEmail;
        $this->mailLogRepository   = $mailLogRepository;
        $this->logger              = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => [
                ['enableTemplateErrorReporting'],
                ['logMessage'],
            ],
        ];
    }

    public function enableTemplateErrorReporting(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if ($message instanceof MailjetMessage) {
            $message->setTemplateErrorEmail($this->errorReportingEmail);

            if ($this->enableErrorDelivery) {
                $message->enableErrorDelivery();
            }
        }
    }

    /**
     * The MessageEvent will be triggered twice. First time, when the Message is queued by the "main" transport.
     * Second time, when the message is sent by the real (ex. smtp) transport.
     */
    public function logMessage(MessageEvent $event): void
    {
        /** @var Message $message */
        $message = $event->getMessage();
        // We log only Message (not RawMessage)
        if (false === $message instanceof Message) {
            return;
        }
        $queued    = $event->isQueued();
        $transport = $event->getTransport();

        try {
            $mailLog = null;
            if (false === $queued) {
                $mailLog = $this->mailLogRepository->findOneBy([
                    'messageId' => MailLog::findMessageIdFromMessage($message),
                ]);
            }

            // We create the log when it doesn't exist. This is normally done at the "first time".
            // Also, we create it, just in case, it was not created at the "first time".
            if (null === $mailLog) {
                $mailLog = new MailLog($message, $transport);
                $this->mailLogRepository->persist($mailLog);
            }
            // We update the log when a sending happens.
            // This is the "second time", at this "time", $event->isQueued() is false.
            if ($mailLog && false === $event->isQueued()) {
                $mailLog->setTransport($transport)->succeed();
            }
            $this->mailLogRepository->flush();
        } catch (ORMException $exception) {
            $this->logger->warning(
                'Could not log the mail sending to the database. Error: ' . $exception->getMessage(),
                ['exception' => $exception]
            );
        }
    }
}
