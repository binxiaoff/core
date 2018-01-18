<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;


use Psr\Log\LoggerInterface;

class TemplateMessage extends \Swift_Message
{
    /** @var int */
    private $templateId;
    /** @var int */
    private $queueId;
    /** @var array */
    private $variables;
    /** @var \DateTime */
    private $toSendAt;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param null|string    $templateId
     * @param null|array     $variables
     * @param null|\DateTime $toSendAt
     * @param null|string    $subject
     * @param null|string    $body
     * @param null|string    $contentType
     * @param null|string    $charset
     */
    public function __construct($templateId, $variables = null, $toSendAt = null, $subject = null, $body = null, $contentType = null, $charset = null)
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
     * @param null|string $subject
     * @param null|string $body
     * @param null|array  $variables
     * @param null|string $contentType
     * @param null|string $charset
     *
     * @return TemplateMessage
     */
    public static function newInstance($subject = null, $body = null, $variables = null, $contentType = null, $charset = null)
    {
        return new self($subject, $body, $variables, $contentType, $charset);
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
     * @return $this
     */
    public function setQueueId($queueId)
    {
        $this->queueId = $queueId;
        return $this;
    }

    /**
     * @param null|array $variables
     *
     * @return $this
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * @param null|\DateTime $toSendAt
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
     * @throws \Swift_RfcComplianceException
     */
    public function setTo($addresses, $name = null)
    {
        $addresses = self::normalizeEmail($addresses);

        parent::setTo($addresses, $name);

        return $this;
    }

    /**
     * @param string|array $addresses
     * @param string|null  $name
     *
     * @return $this
     * @throws \Swift_RfcComplianceException
     */
    public function setReplyTo($addresses, $name = null)
    {
        $addresses = self::normalizeEmail($addresses);

        parent::setReplyTo($addresses, $name);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @return null|int
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

    /**
     * @return null|array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @return null|\Datetime
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
    private static function emailAddressToArray($emails)
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
     * Normalize the emails in order to pass them to Swiftmailer
     *
     * @param string|array $emails
     *
     * @return string|array
     */
    private static function normalizeEmail($emails)
    {
        if (is_string($emails)) {
            return self::removeTimeStampSuffix($emails);
        }

        $normalizedEmails = [];

        $emails = self::emailAddressToArray($emails);

        foreach ($emails as $key => $value) {
            if (is_string($key)) {
                //key is email addr
                $key = self::removeTimeStampSuffix($key);
            } else {
                $value = self::removeTimeStampSuffix($value);
            }
            $normalizedEmails[$key] = $value;
        }

        return $normalizedEmails;
    }

    private static function removeTimeStampSuffix($email)
    {
        if (1 === preg_match('#^(?<email>[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,6})-[0-9]+$#i', $email, $matches)) {
            $email = $matches['email'];
        }

        return $email;
    }
}
