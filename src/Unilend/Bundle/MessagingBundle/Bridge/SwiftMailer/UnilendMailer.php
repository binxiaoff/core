<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Swift_Transport;

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
     * @param null|array                $failedRecipients
     *
     * @return int
     * @throws \Exception
     * @throws \Swift_RfcComplianceException
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null) : int
    {
        if ($message instanceof TemplateMessage) {
            $failedRecipients   = (array) $failedRecipients;
            $mailTemplate       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->find($message->getTemplateId());
            $recipientsAreClean = $this->checkRecipients($message, $failedRecipients);

            if (false === empty($failedRecipients)) {
                $this->logger->warning('Badly formatted recipient(s) removed from message. Concerned recipient(s) : ' . implode(', ', $failedRecipients), [
                    'templateType ' => $mailTemplate->getType(),
                    'function'      => __METHOD__
                ]);
            }

            if (false === $recipientsAreClean) {
                throw new \Exception('Message has no recipient');
            }
        }

        return parent::send($message, $failedRecipients);
    }

    /**
     * @param TemplateMessage $message
     * @param array           $failedRecipients
     *
     * @return bool
     * @throws \Swift_RfcComplianceException
     */
    private function checkRecipients(TemplateMessage $message, array &$failedRecipients) : bool
    {
        $toCount  = count($message->getTo());
        $ccCount  = count($message->getCc());
        $bccCount = count($message->getBcc());

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
    private function checkEmailAddress(string $email) : bool
    {
        $regex = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => 'Regex validation email'])
            ->getValue();

        return 1 === preg_match($regex, $email);
    }
}

