<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Swift_RfcComplianceException;
use Swift_Transport;
use Unilend\Entity\MailTemplates;

class UnilendMailer extends \Swift_Mailer
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param Swift_Transport        $transport
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Swift_Transport $transport, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger        = $logger;
        $this->entityManager = $entityManager;

        parent::__construct($transport);
    }

    /**
     * @param \Swift_Mime_SimpleMessage $message
     * @param array|null                $failedRecipients
     *
     * @throws Exception
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null): int
    {
        if ($message instanceof TemplateMessage) {
            $failedRecipients   = (array) $failedRecipients;
            $mailTemplate       = $this->entityManager->getRepository(MailTemplates::class)->find($message->getTemplateId());
            $recipientsAreClean = $this->checkRecipients($message, $failedRecipients);

            if (false === empty($failedRecipients)) {
                $this->logger->warning('Badly formatted recipient(s) removed from message. Concerned recipient(s) : ' . implode(', ', $failedRecipients), [
                    'templateType ' => $mailTemplate->getType(),
                    'function'      => __METHOD__,
                ]);
            }

            if (false === $recipientsAreClean) {
                throw new Exception('Message has no recipient');
            }
        }

        return parent::send($message, $failedRecipients);
    }

    /**
     * @param TemplateMessage $message
     * @param array           $failedRecipients
     *
     * @throws Swift_RfcComplianceException
     *
     * @return bool
     */
    private function checkRecipients(TemplateMessage $message, array &$failedRecipients): bool
    {
        $toCount  = is_array($message->getTo()) ? count($message->getTo()) : 0;
        $ccCount  = is_array($message->getCc()) ? count($message->getCc()) : 0;
        $bccCount = is_array($message->getBcc()) ? count($message->getBcc()) : 0;

        if (0 === $toCount + $ccCount + $bccCount) {
            return false;
        }

        $cleanTo = [];
        foreach ($message->getTo() as $email => $name) {
            if ($this->checkEmailAddress($email)) {
                $cleanTo[$email] = $name;
            } else {
                $failedRecipients[] = $email;
            }
        }
        $message->setTo($cleanTo);

        $cleanCc = [];
        if (0 !== $ccCount) {
            foreach ($message->getCc() as $email => $name) {
                if ($this->checkEmailAddress($email)) {
                    $cleanCc[$email] = $name;
                } else {
                    $failedRecipients[] = $email;
                }
            }
            $message->setCc($cleanCc);
        }

        $cleanBcc = [];
        if (0 !== $bccCount) {
            foreach ($message->getBcc() as $email => $name) {
                if ($this->checkEmailAddress($email)) {
                    $cleanBcc[$email] = $name;
                } else {
                    $failedRecipients[] = $email;
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
    private function checkEmailAddress(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
