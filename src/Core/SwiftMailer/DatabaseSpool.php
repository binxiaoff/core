<?php

declare(strict_types=1);

namespace Unilend\Core\SwiftMailer;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Swift_ConfigurableSpool;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Unilend\Core\Entity\MailQueue;
use Unilend\Core\Repository\MailQueueRepository;

class DatabaseSpool extends Swift_ConfigurableSpool
{
    private MailQueueRepository $mailQueueRepository;

    public function __construct(MailQueueRepository $mailQueueRepository)
    {
        $this->mailQueueRepository = $mailQueueRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function isStarted()
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function queueMessage(Swift_Mime_SimpleMessage $message)
    {
        $em = $this->mailQueueRepository->getEntityManager();

        $em->persist(new MailQueue($message));
        $em->flush();
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param Swift_Transport $transport        A transport instance
     * @param string[]|null   $failedRecipients An array of failures by-reference
     *
     * @throws Exception
     *
     * @return int The number of sent emails
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null): int
    {
        $limit        = $this->getMessageLimit();
        $limit        = $limit > 0 ? $limit : null;
        $pendingMails = $this->mailQueueRepository->getPendingMails($limit);

        if (!\count($pendingMails)) {
            return 0;
        }

        if (!$transport->isStarted()) {
            $transport->start();
        }

        $em               = $this->mailQueueRepository->getEntityManager();
        $failedRecipients = (array) $failedRecipients;
        $count            = 0;

        foreach ($pendingMails as $item) {
            try {
                $mail = $item->getMessage();
                $count += $transport->send($mail, $failedRecipients);

                if (empty($failedRecipients)) {
                    $item->succeed();
                } else {
                    $item->fail('Mail found in failedRecipients');
                }
            } catch (Exception $exception) {
                $item->fail($exception->getMessage());
            }
        }

        $em->flush();

        return $count;
    }
}
