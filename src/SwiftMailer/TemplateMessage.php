<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class TemplateMessage extends \Swift_Message
{
    /** @var int */
    private $templateId;
    /** @var int */
    private $queueId;
    /** @var array */
    private $variables;
    /** @var DateTimeImmutable */
    private $toSendAt;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string|null            $templateId
     * @param array|null             $variables
     * @param DateTimeImmutable|null $toSendAt
     * @param string|null            $subject
     * @param string|null            $body
     * @param string|null            $contentType
     * @param string|null            $charset
     */
    public function __construct($templateId, array $variables = null, DateTimeImmutable $toSendAt = null, $subject = null, $body = null, $contentType = null, $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);

        $this->templateId = $templateId;

        if ($variables) {
            $this->setVariables($variables);
        }

        if ($toSendAt) {
            $this->setToSendAt($toSendAt);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param int $queueId
     *
     * @return $this
     */
    public function setQueueId($queueId)
    {
        $this->queueId = $queueId;

        return $this;
    }

    /**
     * @param array|null $variables
     *
     * @return $this
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * @param DateTimeImmutable|null $toSendAt
     *
     * @return $this
     */
    public function setToSendAt($toSendAt)
    {
        $this->toSendAt = $toSendAt;

        return $this;
    }

    /**
     * @param string|array $addresses
     * @param string|null  $name
     *
     * @return $this
     */
    public function setTo($addresses, $name = null)
    {
        $addresses = $this->normalizeEmail($addresses);

        parent::setTo($addresses, $name);

        return $this;
    }

    /**
     * @param string|array $addresses
     * @param string|null  $name
     *
     * @return $this
     */
    public function setReplyTo($addresses, $name = null)
    {
        $addresses = $this->normalizeEmail($addresses);

        parent::setReplyTo($addresses, $name);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @return int|null
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

    /**
     * @return array|null
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getToSendAt()
    {
        return $this->toSendAt;
    }

    /**
     * @param string $emails
     *
     * @return array
     */
    private function emailAddressToArray($emails)
    {
        if (is_string($emails)) {
            $formattedEmails = [];
            $emails          = str_replace(';', ',', trim($emails));
            $emails          = explode(',', $emails);

            foreach ($emails as $email) {
                if (empty($email)) {
                    continue;
                }
                if (1 === preg_match('#^(?<name>.*)(\s|)\<(?<email>.*)\>$#', $email, $matches)) {
                    $formattedEmails[trim($matches['email'])] = trim($matches['name']);
                } else {
                    $formattedEmails[] = trim($email);
                }
            }
        } else {
            $formattedEmails = $emails;
        }

        return $formattedEmails;
    }

    /**
     * Normalize the emails in order to pass them to Swiftmailer.
     *
     * @param string|array $emails
     *
     * @return array
     */
    private function normalizeEmail($emails): iterable
    {
        return $this->emailAddressToArray($emails);
    }
}
