<?php

declare(strict_types=1);

namespace Unilend\Service\Mailer;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Swift_Attachment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\Clients;
use Unilend\Entity\MailQueue;
use Unilend\Entity\MailTemplate;
use Unilend\SwiftMailer\Mail;
use Unilend\SwiftMailer\TemplateMessage;
use Unilend\SwiftMailer\TemplateMessageProvider;

class MailQueueManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;
    /** @var string */
    private $temporaryDirectory;

    /**
     * @param EntityManagerInterface  $entityManager
     * @param TemplateMessageProvider $templateMessage
     * @param string                  $temporaryDirectory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TemplateMessageProvider $templateMessage,
        string $temporaryDirectory
    ) {
        $this->entityManager           = $entityManager;
        $this->templateMessageProvider = $templateMessage;
        $this->temporaryDirectory      = $temporaryDirectory;
    }

    /**
     * Put the TemplateMessage to the mail queue.
     *
     * @param TemplateMessage $message
     *
     * @return bool
     */
    public function queue(TemplateMessage $message): bool
    {
        $mailTemplate = $this->entityManager->getRepository(MailTemplate::class)->find($message->getTemplateId());

        $attachments = [];
        foreach ($message->getChildren() as $index => $child) {
            $attachments[$index] = [
                'content-disposition' => $child->getHeaders()->get('Content-Disposition')->getFieldBody(),
                'content-type'        => $child->getHeaders()->get('Content-Type')->getFieldBody(),
                'tmp_file'            => uniqid('', true) . '.attachment',
            ];
            file_put_contents($this->temporaryDirectory . $attachments[$index]['tmp_file'], $child->getBody());
            chmod($this->temporaryDirectory . $attachments[$index]['tmp_file'], 0660);
        }

        $clientRepository = $this->entityManager->getRepository(Clients::class);
        $replyTo          = $message->getReplyTo();

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
                ->setSerializedVariables(json_encode($message->getVariables(), JSON_THROW_ON_ERROR, 512))
                ->setAttachments(json_encode($attachments, JSON_THROW_ON_ERROR, 512))
                ->setReplyTo($replyTo)
                ->setStatus(MailQueue::STATUS_PENDING)
                ->setToSendAt($message->getToSendAt())
                ->setRecipient($recipient)
                ->setIdClient($clientId)
            ;

            $this->entityManager->persist($mailQueue);
            $this->entityManager->flush($mailQueue);
        }

        return true;
    }

    /**
     * Build a TemplateMessage object from a MailQueue object, so that we Swift Mailer can handle it.
     *
     * @param MailQueue $email
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return TemplateMessage
     */
    public function getMessage(MailQueue $email): TemplateMessage
    {
        $message = $this->templateMessageProvider->newMessageByTemplate(
            $email->getIdMailTemplate(),
            json_decode($email->getSerializedVariables(), true, 512, JSON_THROW_ON_ERROR)
        );
        $message
            ->setTo($email->getRecipient())
            ->setQueueId($email->getIdQueue())
        ;

        if (false === empty($email->getReplyTo())) {
            $replyToEmail = $email->getReplyTo();
            $replyToName  = null;

            if (1 === preg_match('#^(?<name>.*)\s?\<(?<email>.*)\>$#', $replyToEmail, $matches)) {
                $replyToEmail = trim($matches['email']);
                $replyToName  = trim($matches['name']);
            }

            $message->setReplyTo($replyToEmail, $replyToName);
        }

        $attachments = json_decode($email->getAttachments(), true, 512, JSON_THROW_ON_ERROR);
        if (is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $swiftAttachment = new Swift_Attachment(file_get_contents($this->temporaryDirectory . $attachment['tmp_file']));
                $swiftAttachment->setContentType($attachment['content-type']);
                $swiftAttachment->setDisposition($attachment['content-disposition']);

                $message->attach($swiftAttachment);

                unlink($this->temporaryDirectory . $attachment['tmp_file']);
            }
        }

        return $message;
    }

    /**
     * Get N (n = $Limit) mails from queue to send.
     *
     * @param int $limit
     *
     * @throws Exception
     *
     * @return MailQueue[]
     */
    public function getMailsToSend($limit): array
    {
        return $this->entityManager->getRepository(MailQueue::class)
            ->getPendingMails($limit)
        ;
    }

    /**
     * @param int|null      $clientId
     * @param string|null   $from
     * @param string|null   $to
     * @param string|null   $subject
     * @param DateTime|null $dateStart
     * @param DateTime|null $dateEnd
     *
     * @return array
     */
    public function searchSentEmails($clientId = null, $from = null, $to = null, $subject = null, DateTime $dateStart = null, DateTime $dateEnd = null)
    {
        return $this->entityManager->getRepository(MailQueue::class)
            ->searchSentEmails($clientId, $from, $to, $subject, $dateStart, $dateEnd)
        ;
    }

    /**
     * @param int $templateId
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function existsInMailQueue($templateId): bool
    {
        return $this->entityManager->getRepository(MailQueue::class)
            ->existsTemplateInMailQueue($templateId)
        ;
    }
}
