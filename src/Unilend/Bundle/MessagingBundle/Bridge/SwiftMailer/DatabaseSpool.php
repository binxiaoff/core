<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Mailjet\Response;
use Unilend\Bundle\MessagingBundle\Service\MailQueueManager;

class DatabaseSpool extends \Swift_ConfigurableSpool
{

    /**
     * @var MailQueueManager
     */
    protected $mailQueueManager;

    /**
     * @param MailQueueManager $mailQueueManager
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(MailQueueManager $mailQueueManager)
    {
        $this->mailQueueManager = $mailQueueManager;
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
     * @param \Swift_Mime_Message $message The message to store
     *
     * @return boolean
     */
    public function queueMessage(\Swift_Mime_Message $message)
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

        $limit = $this->getMessageLimit();
        $limit = $limit > 0 ? $limit : null;
        /** @var \mail_queue[] $emailsToSend */
        $emailsToSend = $this->mailQueueManager->getMailsToSend($limit);

        if (! count($emailsToSend)) {
            return 0;
        }

        $failedRecipients = (array)$failedRecipients;
        $count            = 0;
        $time             = time();

        foreach ($emailsToSend as $email) {
            $sent = false;

            $email->status = \mail_queue::STATUS_PROCESSING;
            $email->update();
            $message = $this->mailQueueManager->getMessage($email);
            /** @var Response $response */
            $response = $transport->send($message, $failedRecipients);
            if ($transport instanceof MailjetTransport && $response instanceof Response) {
                $email->serialized_reponse = json_encode($response->getBody());

                if ($response->success()) {
                    $sent = true;
                } else {
                    $email->serialized_reponse = json_encode($response->getReasonPhrase());
                    $email->status             = \mail_queue::STATUS_ERROR;
                }
            } elseif ($response) {
                $sent                      = true;
                $email->serialized_reponse = 'email sent by the transport other than Mailjet.';
            }

            if ($sent) {
                $count++;
                $email->status  = \mail_queue::STATUS_SENT;
                $email->sent_at = date('Y-m-d H:i:s');
            } else {
                $email->status = \mail_queue::STATUS_ERROR;
            }

            $email->update();

            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $count;
    }
}
