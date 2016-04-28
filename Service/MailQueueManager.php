<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 27/04/2016
 * Time: 12:18
 */

namespace Unilend\Service;


use Unilend\Library\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Library\Bridge\SwiftMailer\TemplateMessageProvider;

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
     * @param TemplateMessage $oMessage
     *
     * @return bool
     */
    public function queue(TemplateMessage $oMessage)
    {
        /** @var \mail_queue $oMailQueue */
        $oMailQueue                       = $this->oEntityManager->getRepository('mail_queue');
        $oMailQueue->id_mail_text         = $oMessage->getTemplateId();
        $oMailQueue->serialized_variables = json_encode($oMessage->getVariables());
        $aRecipients                      = array_keys($oMessage->getTo());
        $oMailQueue->recipient            = array_shift($aRecipients);
        $oMailQueue->status               = \mail_queue::STATUS_PENDING;
        $oMailQueue->to_send_at           = $oMessage->getToSendAt();
        $oMailQueue->create();

        return true;
    }

    /**
     * @param \mail_queue $oEmail
     *
     * @return bool|TemplateMessage
     * @throws \Exception
     */
    public function getMessage(\mail_queue $oEmail)
    {
        /** @var \mails_text $oMailTemplate */
        $oMailTemplate = $this->oEntityManager->getRepository('mails_text');
        if (false === $oMailTemplate->get($oEmail->id_mail_text)) {
            return false;
        }
        /** @var TemplateMessage $oMessage */
        $oMessage = $this->oTemplateMessage->newMessage($oMailTemplate->type, $oMailTemplate->lang, json_decode($oEmail->serialized_variables, true), false);
        $oMessage->addTo($oEmail->recipient);
        return $oMessage;
    }

    /**
     * @param $iLimit
     *
     * @return \mail_queue[]
     */
    public function getMailsToSend($iLimit = null)
    {
        $aEmails = [];

        /** @var \mail_queue $oMailQueue */
        $oMailQueue   = $this->oEntityManager->getRepository('mail_queue');
        $aEmailToSend = $oMailQueue->select('status = ' . \mail_queue::STATUS_PENDING . ' AND to_send_at <= NOW()', '', '', $iLimit);

        if (is_array($aEmailToSend)) {
            foreach ($aEmailToSend as $aEmail) {
                if ($oMailQueue->get($aEmail['id_queue'])) {
                    $aEmails[] = $oMailQueue;
                }
            }
        }

        return $aEmails;
    }
}
