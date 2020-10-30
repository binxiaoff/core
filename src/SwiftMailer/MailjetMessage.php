<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use JsonException;

class MailjetMessage extends \Swift_Message
{
    /**
     * @param string|null $subject
     * @param string|null $body
     * @param string|null $contentType
     * @param string|null $charset
     */
    public function __construct(?string $subject = null, ?string $body = null, ?string $contentType = null, ?string $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);

        $this->enableTemplatingLanguage();
    }

    /**
     * @param int|null $templateId
     *
     * @return MailjetMessage
     */
    public function setTemplate(?int $templateId): self
    {
        $this->getHeaders()->addTextHeader('X-MJ-TemplateID', $templateId);

        return $this;
    }

    /**
     * @return MailjetMessage
     */
    public function enableTemplatingLanguage(): self
    {
        $this->getHeaders()->addTextHeader('X-MJ-TemplateLanguage', '1');

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTemplate(): ?int
    {
        $header =  $this->getHeaders()->get('X-MJ-TemplateID');

        return $header ? $header->getFieldBodyModel() : null;
    }

    /**
     * @return $this
     */
    public function disableTemplatingLanguage(): self
    {
        $this->getHeaders()->removeAll('X-MJ-TemplateLanguage');

        return $this;
    }

    /**
     * @param array $vars
     *
     * @return MailjetMessage
     *
     * @throws JsonException
     */
    public function setVars(array $vars): self
    {
        $this->getHeaders()->addTextHeader('X-MJ-Vars', json_encode($vars, JSON_THROW_ON_ERROR));

        return $this;
    }

    /**
     * @param string|null $email
     *
     * @return MailjetMessage
     */
    public function setTemplateErrorEmail(?string $email): self
    {
        $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorReporting', $email);

        return $this;
    }

    /**
     * @return MailjetMessage
     */
    public function enableErrorDelivery(): self
    {
        $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorDeliver', 'deliver');

        return $this;
    }

    /**
     * @return MailjetMessage
     */
    public function disableErrorDelivery(): self
    {
        $this->getHeaders()->removeAll('X-MJ-TemplateErrorDeliver');

        return $this;
    }
}
