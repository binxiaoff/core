<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Doctrine\ORM\EntityManager;
use Mailjet\Response;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class MailQueueManager
{
    /** @var EntityManagerSimulator */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $templateMessage;
    /** @var string */
    private $sharedTemporaryPath;
    /** @var LoggerInterface */
    private $logger;
    /** @var  EntityManager */
    private $em;

    /**
     * MailQueueManager constructor.
     *
     * @param EntityManagerSimulator $entityManager
     * @param EntityManager $em
     * @param TemplateMessageProvider $templateMessage
     * @param LoggerInterface $logger
     * @param $sharedTemporaryPath
     */
    public function __construct(EntityManagerSimulator $entityManager, EntityManager $em, TemplateMessageProvider $templateMessage, LoggerInterface $logger, $sharedTemporaryPath)
    {
        $this->entityManager       = $entityManager;
        $this->templateMessage     = $templateMessage;
        $this->sharedTemporaryPath = $sharedTemporaryPath;
        $this->logger              = $logger;
        $this->em                  = $em;
    }

    /**
     * Put the TemplateMessage to the mail queue
     *
     * @param TemplateMessage $message
     *
     * @return bool
     */
    public function queue(TemplateMessage $message)
    {
        $count = (
            count((array) $message->getTo())
            + count((array) $message->getCc())
            + count((array) $message->getBcc())
        );

        if (0 === $count) {
            $completeTrace     = debug_backtrace();
            $backtrace = [];

            foreach ($completeTrace as $key => $trace){
                $backtrace[$key]['file'] = isset($trace['file']) ? $trace['file'] : '';
                $backtrace[$key]['line'] = isset($trace['line']) ? $trace['line'] : '';
            }

            $this->logger->error('email address empty : ', ['template' => $message->getTemplateId(), 'backtrace'  => $backtrace]);
            return false;
        }


        $attachments = [];
        foreach ($message->getChildren() as $index => $child) {
            $attachments[$index] = [
                'content-disposition' => $child->getHeaders()->get('Content-Disposition')->getFieldBody(),
                'content-type'        => $child->getHeaders()->get('Content-Type')->getFieldBody(),
                'tmp_file'            => uniqid() . '.attachment'
            ];
            file_put_contents($this->sharedTemporaryPath . $attachments[$index]['tmp_file'], $child->getBody());
            chmod($this->sharedTemporaryPath . $attachments[$index]['tmp_file'], 0660);
        }

        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        /** @var \mail_queue $mailQueue */
        $mailQueue                       = $this->entityManager->getRepository('mail_queue');
        $mailQueue->id_mail_template     = $message->getTemplateId();
        $mailQueue->serialized_variables = json_encode($message->getVariables());
        $mailQueue->attachments          = json_encode($attachments);
        $recipients                      = TemplateMessage::emailAddressToString($message->getTo());
        $replyTo                         = is_array($message->getReplyTo()) ? TemplateMessage::emailAddressToString($message->getReplyTo()) : null;

        if (1 === count($message->getTo()) && $client->get($recipients, 'email')) {
            $mailQueue->id_client = $client->id_client;
        }

        $mailQueue->recipient  = $recipients;
        $mailQueue->reply_to   = $replyTo;
        $mailQueue->status     = \mail_queue::STATUS_PENDING;
        if ($message->getToSendAt() instanceof \DateTime) {
            $mailQueue->to_send_at = $message->getToSendAt()->format('Y-m-d H:i:s');
        }
        $mailQueue->create();

        return true;
    }

    /**
     * Build a TemplateMessage object from a mail_queue object, so that we Swift Mailer can handle it.
     *
     * @param MailQueue $email
     *
     * @return bool|TemplateMessage
     * @throws \Exception
     */
    public function getMessage(MailQueue $email)
    {
        /** @var \mail_templates $mailTemplate */
        $mailTemplate = $this->entityManager->getRepository('mail_templates');
        if (false === $mailTemplate->get($email->getIdMailTemplate())) {
            return false;
        }

        /** @var TemplateMessage $message */
        $message = $this->templateMessage->newMessage($mailTemplate->type, json_decode($email->getSerializedVariables(), true), false);
        $message
            ->setTo($email->getRecipient())
            ->setQueueId($email->getIdQueue());

        if (false === empty($email->getReplyTo())) {
            $message->setReplyTo($email->getReplyTo());
        }
        $attachments = json_decode($email->getAttachments(), true);
        if (is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $swiftAttachment = \Swift_Attachment::newInstance(file_get_contents($this->sharedTemporaryPath . $attachment['tmp_file']));
                $swiftAttachment->setContentType($attachment['content-type']);
                $swiftAttachment->setDisposition($attachment['content-disposition']);

                $message->attach($swiftAttachment);

                unlink($this->sharedTemporaryPath . $attachment['tmp_file']);
            }
        }

        return $message;
    }

    /**
     * Get N (n = $Limit) mails from queue to send
     *
     * @param $limit
     *
     * @return MailQueue[]
     */
    public function getMailsToSend($limit)
    {
        return $this->em->getRepository('UnilendCoreBusinessBundle:MailQueue')->getPendingMails($limit);
    }

    /**
     * @param int|null       $clientId
     * @param string|null    $from
     * @param string|null    $to
     * @param string|null    $subject
     * @param \DateTime|null $dateStart
     * @param \DateTime|null $dateEnd
     *
     * @return array
     */
    public function searchSentEmails($clientId = null, $from = null, $to = null, $subject = null, \DateTime $dateStart = null, \DateTime $dateEnd = null)
    {
        /** @var \mail_queue $mailQueue */
        $mailQueue = $this->entityManager->getRepository('mail_queue');
        return $mailQueue->searchSentEmails($clientId, $from, $to, $subject, $dateStart, $dateEnd);
    }

    /**
     * @param int $templateId
     *
     * @return bool
     */
    public function existsInMailQueue($templateId)
    {
        /** @var \mail_queue $mailQueue */
        $mailQueue = $this->entityManager->getRepository('mail_queue');
        return $mailQueue->exist($templateId, 'id_mail_template');
    }

    /**
     * @param MailQueue $email
     * @param Response  $response
     *
     * @return null|integer
     */
    public function findMessageId(MailQueue $email, Response $response)
    {
        $messageId = null;
        if ($email->getRecipient()) {
            // Get first recipient (see TECH-241)
            $recipient = array_values(explode(',', $email->getRecipient()))[0];
            if (1 === preg_match('#^(?<name>.*)(\s|)\<(?<email>.*)\>$#', $recipient, $matches)) {
                $firstRecipient= trim($matches['email']);
            } else {
                $firstRecipient = trim($recipient);
            }
            $body = $response->getBody();
            if (false === empty($body['Sent'])) {
                foreach ($body['Sent'] as $sent) {
                    if($sent['Email'] === $firstRecipient) {
                        $messageId = $sent['MessageID'];
                        break;
                    }
                }
            }
        }

        return $messageId;
    }
}
