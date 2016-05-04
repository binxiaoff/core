<?php

namespace Unilend\Bridge\SwiftMailer;


class TemplateMessage extends \Swift_Message
{
    private $templateId;
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
}
