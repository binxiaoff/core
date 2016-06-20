<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 27/04/2016
 * Time: 12:18
 */

namespace Unilend\Bundle\MessagingBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class MailQueueManager
{
    /** @var EntityManager */
    private $oEntityManager;
    /** @var TemplateMessageProvider */
    private $oTemplateMessage;

    /**
     * MailQueueManager constructor.
     *
     * @param EntityManager           $oEntityManager
     * @param TemplateMessageProvider $oTemplateMessage
     */
    public function __construct(EntityManager $oEntityManager, TemplateMessageProvider $oTemplateMessage)
    {
        $this->oEntityManager   = $oEntityManager;
        $this->oTemplateMessage = $oTemplateMessage;
    }

    /**
     * Put the TemplateMessage to the mail queue
     *
     * @param TemplateMessage $oMessage
     *
     * @return bool
     */
    public function queue(TemplateMessage $oMessage)
    {
        /** @var \mail_queue $oMailQueue */
        $oMailQueue                       = $this->oEntityManager->getRepository('mail_queue');
        $oMailQueue->id_mail_template     = $oMessage->getTemplateId();
        $oMailQueue->serialized_variables = json_encode($oMessage->getVariables());
        $recipients                       = TemplateMessage::recipientsString($oMessage->getTo());
        /** @var \clients $client */
        $client                           = $this->oEntityManager->getRepository('clients');
        // try to find client id
        if (1 === count($oMessage->getTo()) && $client->get($recipients, 'email')) {
            $oMailQueue->id_client = $client->id_client;
        }
        $oMailQueue->recipient  = $recipients;
        $oMailQueue->status     = \mail_queue::STATUS_PENDING;
        $oMailQueue->to_send_at = $oMessage->getToSendAt();
        $oMailQueue->create();

        return true;
    }

    /**
     * Build a TemplateMessage object from a mail_queue object, so that we Swift Mailer can handle it.
     *
     * @param \mail_queue $oEmail
     *
     * @return bool|TemplateMessage
     * @throws \Exception
     */
    public function getMessage(\mail_queue $oEmail)
    {
        /** @var \mail_templates $oMailTemplate */
        $oMailTemplate = $this->oEntityManager->getRepository('mail_templates');
        if (false === $oMailTemplate->get($oEmail->id_mail_template)) {
            return false;
        }
        /** @var TemplateMessage $oMessage */
        $oMessage = $this->oTemplateMessage->newMessage($oMailTemplate->type, json_decode($oEmail->serialized_variables, true), false);
        $oMessage->setTo(TemplateMessage::recipientsArray($oEmail->recipient));
        return $oMessage;
    }

    /**
     * Get N (n = $Limit) mails from queue to send
     *
     * @param $iLimit
     *
     * @return \mail_queue[]
     */
    public function getMailsToSend($iLimit = null)
    {
        $aEmails = [];

        /** @var \mail_queue $oMailQueue */
        $oMailQueue   = $this->oEntityManager->getRepository('mail_queue');
        $aEmailToSend = $oMailQueue->select('status = ' . \mail_queue::STATUS_PENDING . ' AND to_send_at <= NOW()', 'id_queue ASC', '', $iLimit);

        if (is_array($aEmailToSend)) {
            foreach ($aEmailToSend as $aEmail) {
                if ($oMailQueue->get($aEmail['id_queue'])) {
                    $aEmails[] = clone $oMailQueue;
                }
            }
        }

        return $aEmails;
    }

    /**
     * @param int|null $iClientId
     * @param string|null $sFrom
     * @param string|null $sTo
     * @param string|null $sSubject
     * @param \DateTime|null $oDateStart
     * @param \DateTime|null $oDateEnd
     * @param int|null $iLimit
     *
     * @return array
     */
    public function searchSentEmails($iClientId = null, $sFrom = null, $sTo = null, $sSubject = null, \DateTime $oDateStart = null, \DateTime $oDateEnd = null, $iLimit = null)
    {
        /** @var \mail_queue $oMailQueue */
        $oMailQueue = $this->oEntityManager->getRepository('mail_queue');
        return $oMailQueue->searchSentEmails($iClientId, $sFrom, $sTo, $sSubject, $oDateStart, $oDateEnd, $iLimit);
    }

    /**
     * @param int $iTemplateID
     *
     * @return bool
     */
    public function existsInMailQueue($iTemplateID)
    {
        /** @var \mail_queue $oMailQueue */
        $oMailQueue   = $this->oEntityManager->getRepository('mail_queue');
        return $oMailQueue->exist($iTemplateID, 'id_mail_template');
    }
}
