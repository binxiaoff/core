<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use DateTime;
use Doctrine\ORM\{EntityManagerInterface, OptimisticLockException};
use Mailjet\Response;
use Psr\Log\LoggerInterface;
use Swift_ConfigurableSpool;
use Swift_Mime_SimpleMessage;
use Swift_RfcComplianceException;
use Swift_Transport;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\MailQueue;
use Unilend\Service\Mailer\MailQueueManager;

class DatabaseSpool extends Swift_ConfigurableSpool
{
    /** @var MailQueueManager */
    protected $mailQueueManager;
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param MailQueueManager       $mailQueueManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(MailQueueManager $mailQueueManager, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->mailQueueManager = $mailQueueManager;
        $this->entityManager    = $entityManager;
        $this->logger           = $logger;
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start(): void
    {
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop(): void
    {
    }

    /**
     * Tests if this Spool mechanism has started.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return true;
    }

    /**
     * Queues a message.
     *
     * @param Swift_Mime_SimpleMessage $message The message to store
     *
     * @return bool
     */
    public function queueMessage(Swift_Mime_SimpleMessage $message): bool
    {
        return $this->mailQueueManager->queue($message);
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param Swift_Transport $transport        A transport instance
     * @param string[]        $failedRecipients An array of failures by-reference
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int The number of sent emails
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null): int
    {
        if (!$transport->isStarted()) {
            $transport->start();
        }

        $limit        = $this->getMessageLimit();
        $limit        = $limit > 0 ? $limit : null;
        $emailsToSend = $this->mailQueueManager->getMailsToSend($limit);

        if (!count($emailsToSend)) {
            return 0;
        }

        $failedRecipients = (array) $failedRecipients;
        $count            = 0;
        $batches          = array_chunk($emailsToSend, 50);

        /** @var MailQueue[] $batch */
        foreach ($batches as $index => $batch) {
            foreach ($batch as $email) {
                $email->setStatus(MailQueue::STATUS_PROCESSING);

                try {
                    $message = $this->mailQueueManager->getMessage($email);
                } catch (Swift_RfcComplianceException $exception) {
                    $this->logger->error(
                        'Unable to retrieve message ' . $email->getId() . '. Got exception: ' . $exception->getMessage(),
                        ['file' => $exception->getFile(), 'line' => $exception->getFile()]
                    );

                    continue;
                }

                $response = $transport->send($message, $failedRecipients);

                if (!($transport instanceof MailjetTransport)) {
                    if ($response) {
                        ++$count;
                        $email->setStatus(MailQueue::STATUS_SENT);
                        $email->setSentAt(new DateTime());
                    } else {
                        $email->setStatus(MailQueue::STATUS_ERROR);
                    }
                }
            }

            if ($transport instanceof MailjetTransport) {
                /** @var Response $response */
                $response = $transport->stop();

                if ($response instanceof Response) {
                    if ($response->success()) {
                        $count += count($batch);
                        foreach ($batch as $email) {
                            $email->setStatus(MailQueue::STATUS_SENT);
                            $email->setSentAt(new DateTime());
                            $email->setIdMessageMailjet($transport->getMessageId($email, $response));
                        }
                    } else {
                        $errorEmails  = [];
                        $reasonPhrase = json_encode($response->getReasonPhrase());

                        foreach ($batch as $email) {
                            $email->setStatus(MailQueue::STATUS_ERROR);
                            $email->setErrorMailjet($reasonPhrase);

                            $errorEmails[] = $email->getId();
                        }

                        if ($response->getBody() && isset($response->getBody()['Messages'])) {
                            $serializedMessage = json_encode($response->getBody()['Messages'], JSON_THROW_ON_ERROR, 512);
                            $this->logger->warning(
                                'An error occurred while sending emails via Mailjet: ' . $reasonPhrase . '. Response was ' . $serializedMessage,
                                [
                                    'emails'   => $errorEmails,
                                    'class'    => __CLASS__,
                                    'function' => __FUNCTION__,
                                ]
                            );
                        }
                    }
                }
            }
        }

        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException $exception) {
            $this->logger->error(
                'Unable to save message queue flush due to Doctrine error: ' . $exception->getMessage(),
                ['file' => $exception->getFile(), 'line' => $exception->getFile()]
            );
        }

        return $count;
    }
}
