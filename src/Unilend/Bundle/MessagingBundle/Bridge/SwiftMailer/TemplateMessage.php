<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;


use Psr\Log\LoggerInterface;

class TemplateMessage extends \Swift_Message
{
    /** @var int */
    private $templateId;
    /** @var array */
    private $variables;
    /** @var \DateTime */
    private $toSendAt;
    /** @var LoggerInterface */
    private $logger;

    /**
     * TemplateMessage constructor.
     *
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

    public function setTo($addresses, $name = null)
    {
        if (is_string($addresses)) {
            $addresses = self::emailAddressToArray($addresses);
        }

        try {
            parent::setTo($addresses, $name);
        } catch (\Swift_RfcComplianceException $exception) {
            if ($this->logger instanceof LoggerInterface) {
                $trace = debug_backtrace();
                $this->logger->alert($exception->getMessage(), array('address' => $addresses, 'template' => $this->templateId, 'file' => $trace[0]['file'], 'line' => $trace[0]['line']));
            }
        }

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
     * @param array $emails
     *
     * @return string
     */
    public static function emailAddressToString(array $emails)
    {
        if (is_array($emails)) {
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
        } else {
            $formattedEmails = $emails;
        }

        return $formattedEmails;
    }

    /**
     * @param string $emails
     *
     * @return array
     */
    public static function emailAddressToArray($emails)
    {
        if (is_string($emails)) {
            $formattedEmails = [];
            $emails          = str_replace(';', ',', trim($emails));
            $emails          = explode(',', $emails);

            foreach ($emails as $email) {
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
}
