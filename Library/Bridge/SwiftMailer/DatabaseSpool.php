<?php

namespace Unilend\Library\Bridge\SwiftMailer;

use Mailjet\Resources;
use Mailjet\Response;
use Unilend\Service\MailQueueManager;

class DatabaseSpool extends \Swift_ConfigurableSpool
{

    /**
     * @var MailQueueManager
     */
    protected $oMailQueueManager;

    /**
     * @param MailQueueManager $oMailQueueManager
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(MailQueueManager $oMailQueueManager)
    {
        $this->oMailQueueManager = $oMailQueueManager;
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
     * @param \Swift_Mime_Message $oMessage The message to store
     *
     * @return boolean
     */
    public function queueMessage(\Swift_Mime_Message $oMessage)
    {
        return $this->oMailQueueManager->queue($oMessage);
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param \Swift_Transport $oTransport         A transport instance
     * @param string[]         &$aFailedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function flushQueue(\Swift_Transport $oTransport, &$aFailedRecipients = null)
    {
        if (! $oTransport->isStarted()) {
            $oTransport->start();
        }

        $iLimit = $this->getMessageLimit();
        $iLimit = $iLimit > 0 ? $iLimit : null;
        /** @var \mail_queue[] $aEmailToSend */
        $aEmailToSend = $this->oMailQueueManager->getMailsToSend($iLimit);

        if (! count($aEmailToSend)) {
            return 0;
        }

        $aFailedRecipients = (array)$aFailedRecipients;
        $iCount            = 0;
        $iTime             = time();

        foreach ($aEmailToSend as $oEmail) {
            $oEmail->status = \mail_queue::STATUS_PROCESSING;
            $oEmail->update();
            $oMessage = $this->oMailQueueManager->getMessage($oEmail);
            /** @var Response $oResponse */
            $oResponse = $oTransport->send($oMessage, $aFailedRecipients);
            $oEmail->serialized_reponse = json_encode($oResponse->getData());

            if ($oResponse->success()) {
                $iCount ++;
                $oEmail->status  = \mail_queue::STATUS_SENT;
                $oEmail->sent_at = date('Y-m-d H:i:s');
            } else {
                $oEmail->status  = \mail_queue::STATUS_ERROR;
            }

            $oEmail->update();

            if ($this->getTimeLimit() && (time() - $iTime) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $iCount;
    }
}
