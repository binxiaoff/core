<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class MailQueueManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $templateMessage;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $sharedTemporaryPath;

    /**
     * @param EntityManager           $entityManager
     * @param TemplateMessageProvider $templateMessage
     * @param LoggerInterface         $logger
     * @param string                  $sharedTemporaryPath
     */
    public function __construct(
        EntityManager $entityManager,
        TemplateMessageProvider $templateMessage,
        LoggerInterface $logger,
        $sharedTemporaryPath
    )
    {
        $this->entityManager       = $entityManager;
        $this->templateMessage     = $templateMessage;
        $this->logger              = $logger;
        $this->sharedTemporaryPath = $sharedTemporaryPath;
    }

    /**
     * Put the TemplateMessage to the mail queue
     *
     * @param TemplateMessage $message
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Swift_RfcComplianceException
     */
    public function queue(TemplateMessage $message) : bool
    {
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->find($message->getTemplateId());

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

        $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $replyTo          = $this->emailAddressToString($message->getReplyTo());

        foreach ($message->getTo() as $email => $name) {
            $recipient = $email;

            if (false === empty($name)) {
                $recipient = $name . ' <' . $email . '>';
            }

            $clientId = null;
            $clients  = $clientRepository->findBy(['email' => $email]);

            if (1 === count($clients)) {
                $clientId = $clients[0]->getIdClient();
            }

            $mailQueue = new MailQueue();
            $mailQueue
                ->setIdMailTemplate($mailTemplate)
                ->setSerializedVariables(json_encode($message->getVariables()))
                ->setAttachments(json_encode($attachments))
                ->setReplyTo($replyTo)
                ->setStatus(MailQueue::STATUS_PENDING)
                ->setToSendAt($message->getToSendAt())
                ->setRecipient($recipient)
                ->setIdClient($clientId);

            $this->entityManager->persist($mailQueue);
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * Build a TemplateMessage object from a MailQueue object, so that we Swift Mailer can handle it.
     *
     * @param MailQueue $email
     *
     * @return TemplateMessage
     * @throws \Swift_RfcComplianceException
     */
    public function getMessage(MailQueue $email)
    {
        $message = $this->templateMessage->newMessageByTemplate($email->getIdMailTemplate(), json_decode($email->getSerializedVariables(), true), false);
        $message
            ->setTo($email->getRecipient())
            ->setQueueId($email->getIdQueue());

        if (false === empty($email->getReplyTo())) {
            $replyToEmail = $email->getReplyTo();
            $replyToName  = null;

            if (1 === preg_match('#^(?<name>.*)\s?\<(?<email>.*)\>$#', $replyToEmail, $matches)) {
                $replyToEmail = trim($matches['email']);
                $replyToName  = trim($matches['name']);
            }

            $message->setReplyTo($replyToEmail, $replyToName);
        }

        $attachments = json_decode($email->getAttachments(), true);
        if (is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $swiftAttachment = new \Swift_Attachment(file_get_contents($this->sharedTemporaryPath . $attachment['tmp_file']));
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
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailQueue')
            ->getPendingMails($limit);
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
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailQueue')
            ->searchSentEmails($clientId, $from, $to, $subject, $dateStart, $dateEnd);
    }

    /**
     * @param int $templateId
     *
     * @return bool
     */
    public function existsInMailQueue($templateId)
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailQueue')
            ->existsTemplateInMailQueue($templateId);
    }

    /**
     * @param array $emails
     *
     * @return string
     */
    private function emailAddressToString(array $emails)
    {
        $formattedEmails = '';
        foreach ($emails as $email => $name) {
            if ($formattedEmails) {
                $formattedEmails .= ', ';
            }
            if ($name) {
                $formattedEmails .= $name . ' <' . $email . '>';
            } else {
                $formattedEmails .= $email;
            }
        }

        return $formattedEmails;
    }
}
