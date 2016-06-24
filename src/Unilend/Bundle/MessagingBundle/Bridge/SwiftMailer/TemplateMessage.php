<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;


class TemplateMessage extends \Swift_Message
{
    /** @var int */
    private $templateId;
    /** @var  array */
    private $variables;
    /** @var  \DateTime */
    private $toSendAt;

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
            $addresses = self::recipientsArray($addresses);
        }

        return parent::setTo($addresses, $name);
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
     * @param array $recipients
     *
     * @return string
     */
    public static function recipientsString(array $recipients)
    {
        if (is_array($recipients)) {
            $recipientsFormatted = '';
            foreach ($recipients as $email => $name) {
                if ($recipientsFormatted) {
                    $recipientsFormatted .= ', ';
                }
                if ($name) {
                    $recipientsFormatted .= $name . ' <' . $email . '>';
                } else {
                    $recipientsFormatted .= $email;
                }
            }
        } else {
            $recipientsFormatted = $recipients;
        }

        return $recipientsFormatted;
    }

    /**
     * @param string $recipients
     *
     * @return array
     */
    public static function recipientsArray($recipients)
    {
        if (is_string($recipients)) {
            $recipientsFormatted = [];
            $recipients          = str_replace(';', ',', $recipients);
            $recipients          = explode(',', $recipients);

            foreach ($recipients as $recipient) {
                if (1 === preg_match('#^(?<name>.*)(\s|)\<(?<email>.*)\>$#', $recipient, $matches)) {
                    $recipientsFormatted[trim($matches['email'])] = trim($matches['name']);
                } else {
                    $recipientsFormatted[] = trim($recipient);
                }
            }
        } else {
            $recipientsFormatted = trim($recipients);
        }

        return $recipientsFormatted;
    }
}
