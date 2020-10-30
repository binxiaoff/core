<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Swift_ConfigurableSpool;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Unilend\Entity\MailQueue;
use Unilend\Repository\MailQueueRepository;

class DatabaseSpool extends Swift_ConfigurableSpool
{
    /**
     * @var MailQueueRepository
     */
    private MailQueueRepository $mailQueueRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param MailQueueRepository $mailQueueRepository
     * @param LoggerInterface     $logger
     */
    public function __construct(MailQueueRepository $mailQueueRepository, LoggerInterface $logger)
    {
        $this->mailQueueRepository = $mailQueueRepository;
        $this->logger = $logger;
    }


    /**
     * @inheritDoc
     */
    public function start()
    {
    }

    /**
     * @inheritDoc
     */
    public function stop()
    {
    }

    /**
     * @inheritDoc
     */
    public function isStarted()
    {
    }

    /**
     * @inheritDoc
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
     * @return int The number of sent emails
     *
     * @throws Exception
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null): int
    {
        if (!$transport->isStarted()) {
            $transport->start();
        }

        $limit        = $this->getMessageLimit();
        $limit        = $limit > 0 ? $limit : null;
        $pendingMails = $this->mailQueueRepository->getPendingMails($limit);

        if (!count($pendingMails)) {
            return 0;
        }

        $em = $this->mailQueueRepository->getEntityManager();
        $failedRecipients = (array) $failedRecipients;
        $count            = 0;

        foreach ($pendingMails as $item) {
            $mail = $item->getMessage();

            $count += $transport->send($mail, $failedRecipients);

            if (empty($failedRecipients)) {
                $item->succeed();
            } else {
                $item->fail();
            }

            $em->persist($item);
        }

        $em->flush();

        return $count;
    }
}
