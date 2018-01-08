<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Doctrine\ORM\EntityManager;
use Mailjet\Response;
use Psr\Log\LoggerInterface;
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
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Swift_RfcComplianceException
     */
    public function queue(TemplateMessage $message) : bool
    {
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->find($message->getTemplateId());

        if (false == $this->checkRecipients($message)) {
            $this->logger->warning('Email not inserted into queue due to badly formatted recipient(s) : ', [
                'templateType ' => $mailTemplate->getType(),
                'function'      => __METHOD__
            ]);

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

        $recipients   = TemplateMessage::emailAddressToString($message->getTo());
        $replyTo      = is_array($message->getReplyTo()) ? TemplateMessage::emailAddressToString($message->getReplyTo()) : null;
        $clientId     = null;

        if (1 === count($message->getTo())) {
            $clients = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findBy(['email' => $recipients]);

            if (1 === count($clients)) {
                $clientId = $clients[0]->getIdClient();
            }
        }

        $mailQueue = new MailQueue();
        $mailQueue
            ->setIdMailTemplate($mailTemplate)
            ->setSerializedVariables(json_encode($message->getVariables()))
            ->setAttachments(json_encode($attachments))
            ->setRecipient($recipients)
            ->setIdClient($clientId)
            ->setReplyTo($replyTo)
            ->setStatus(MailQueue::STATUS_PENDING)
            ->setToSendAt($message->getToSendAt());

        $this->entityManager->persist($mailQueue);
        $this->entityManager->flush($mailQueue);

        return true;
    }

    /**
     * @param TemplateMessage $message
     *
     * @return bool
     * @throws \Swift_RfcComplianceException
     */
    private function checkRecipients(TemplateMessage $message) : bool
    {
        $toCount  = count((array) $message->getTo());
        $ccCount  = count((array) $message->getCc());
        $bccCount = count((array) $message->getBcc());

        if (0 === $toCount + $ccCount + $bccCount) {
            return false;
        }

        $cleanTo = [];
        foreach($message->getTo() as $email => $name) {
            if ($this->checkEmailAddress($email)) {
                $cleanTo[$email] = $name;
            }
        }
        $message->setTo($cleanTo);

        $cleanCc = [];
        if (0 !== $ccCount) {
            foreach($message->getCc() as $email => $name) {
                if ($this->checkEmailAddress($email)) {
                    $cleanCc[$email] = $name;
                }
            }
            $message->setCc($cleanCc);
        }

        $cleanBcc = [];
        if (0 !== $bccCount) {
            foreach($message->getBcc() as $email => $name) {
                if ($this->checkEmailAddress($email)) {
                    $cleanBcc[$email] = $name;
                }
            }
            $message->setBcc($cleanBcc);
        }

        if (0 === count(array_merge($cleanTo, $cleanBcc, $cleanCc))) {
            return false;
        }

        return true;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    private function checkEmailAddress(string $email) : bool
    {
        if (1 !== preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email)) {
            $this->logger->warning('Email is badly formatted. Email : ' . $email, []);
            return false;
        }

        return true;
    }

    /**
     * Build a TemplateMessage object from a MailQueue object, so that we Swift Mailer can handle it.
     *
     * @param MailQueue $email
     *
     * @return bool|TemplateMessage
     * @throws \Exception
     */
    public function getMessage(MailQueue $email)
    {
        $message = $this->templateMessage->newMessageByTemplate($email->getIdMailTemplate(), json_decode($email->getSerializedVariables(), true), false);
        $message
            ->setTo($email->getRecipient())
            ->setQueueId($email->getIdQueue());

        if (false === empty($email->getReplyTo())) {
            $message->setReplyTo($email->getReplyTo());
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
                    if ($sent['Email'] === $firstRecipient) {
                        $messageId = $sent['MessageID'];
                        break;
                    }
                }
            }
        }

        return $messageId;
    }
}
