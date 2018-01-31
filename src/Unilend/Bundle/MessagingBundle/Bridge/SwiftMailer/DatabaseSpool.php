<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Mailjet\Response;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue;
use Unilend\Bundle\MessagingBundle\Service\MailQueueManager;

class DatabaseSpool extends \Swift_ConfigurableSpool
{
    /** @var MailQueueManager */
    protected $mailQueueManager;
    /** @var EntityManager */
    protected $entityManager;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param MailQueueManager $mailQueueManager
     * @param EntityManager    $entityManager
     * @param LoggerInterface  $logger
     */
    public function __construct(MailQueueManager $mailQueueManager, EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->mailQueueManager = $mailQueueManager;
        $this->entityManager    = $entityManager;
        $this->logger           = $logger;
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start()
    {
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop()
    {
    }

    /**
     * Tests if this Spool mechanism has started.
     *
     * @return boolean
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Queues a message.
     *
     * @param \Swift_Mime_SimpleMessage $message The message to store
     *
     * @return boolean
     */
    public function queueMessage(\Swift_Mime_SimpleMessage $message)
    {
        return $this->mailQueueManager->queue($message);
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param \Swift_Transport $transport         A transport instance
     * @param string[]         &$failedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        if (! $transport->isStarted()) {
            $transport->start();
        }

        $limit        = $this->getMessageLimit();
        $limit        = $limit > 0 ? $limit : null;
        $emailsToSend = $this->mailQueueManager->getMailsToSend($limit);

        if (! count($emailsToSend)) {
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
                } catch (\Swift_RfcComplianceException $exception) {
                    $this->logger->error(
                        'Unable to retrieve message ' . $email->getIdQueue() . '. Got exception: ' . $exception->getMessage(),
                        ['file' => $exception->getFile(), 'line' => $exception->getFile()]
                    );
                    continue;
                }

                $response = $transport->send($message, $failedRecipients);

                if (! ($transport instanceof MailjetTransport)) {
                    if ($response) {
                        $count++;
                        $email->setStatus(MailQueue::STATUS_SENT);
                        $email->setSentAt(new \DateTime());
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
                            $email->setSentAt(new \DateTime());
                            $email->setIdMessageMailjet($transport->getMessageId($email, $response));
                        }
                    } else {
                        $reasonPhrase = json_encode($response->getReasonPhrase());

                        foreach ($batch as $email) {
                            $email->setStatus(MailQueue::STATUS_ERROR);
                            $email->setErrorMailjet($reasonPhrase);
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
