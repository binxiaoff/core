<?php

declare(strict_types=1);

namespace Unilend\Core\SwiftMailer;

use InvalidArgumentException;
use JsonException;
use Unilend\Core\Traits\ConstantsAwareTrait;

class MailjetMessage extends \Swift_Message
{
    use ConstantsAwareTrait;

    public const TEMPLATE_STAFF_USER_INITIALISATION = 1851115;
    public const TEMPLATE_USER_PASSWORD_REQUEST = 1852070;
    public const TEMPLATE_PUBLICATION_PROSPECT_COMPANY = 1852083;
    public const TEMPLATE_PUBLICATION_UNINITIALIZED_USER = 1852104;
    public const TEMPLATE_PUBLICATION = 1853426;
    public const TEMPLATE_SYNDICATION_PROSPECT_COMPANY = 1853443;
    public const TEMPLATE_SYNDICATION_UNINITIALIZED_USER = 1853467;
    public const TEMPLATE_SYNDICATION = 1853479;
    public const TEMPLATE_PROJECT_FILE_UPLOADED = 1853491;
    public const TEMPLATE_PARTICIPANT_REPLY = 1853502;
    public const TEMPLATE_ARRANGER_INVITATION_EXTERNAL_BANK = 1853530;

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
     * @return int|null
     */
    public function getTemplateId(): ?int
    {
        $header =  $this->getHeaders()->get('X-MJ-TemplateID');

        return $header ? $header->getFieldBodyModel() : null;
    }

    /**
     * @param int|null $templateId
     *
     * @return MailjetMessage
     */
    public function setTemplateId(?int $templateId): self
    {
        if ($templateId && false === \in_array($templateId, static::getAvailableTemplates(), true)) {
            throw new InvalidArgumentException('This template id does not exist');
        }

        if ($templateId) {
            $this->getHeaders()->addTextHeader('X-MJ-TemplateID', $templateId);
        } else {
            $this->getHeaders()->removeAll('X-MJ-TemplateID');
        }

        return $this;
    }

    /**
     * @param array $vars
     *
     * @return MailjetMessage
     *
     * @throws JsonException
     */
    public function setVars(array $vars = []): self
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
        // Remove the previously existing headers to prevent the duplication.
        $this->getHeaders()->removeAll('X-MJ-TemplateErrorReporting');
        if ($email) {
            $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorReporting', $email);
        }

        return $this;
    }

    /**
     * @return MailjetMessage
     */
    public function enableErrorDelivery(): self
    {
        // Remove the previously existing headers to prevent the duplication.
        $this->getHeaders()->removeAll('X-MJ-TemplateErrorDeliver');
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

    /**
     * @return array|int[]
     */
    private static function getAvailableTemplates(): array
    {
        return static::getConstants('TEMPLATE_');
    }

    /**
     * @return MailjetMessage
     */
    private function enableTemplatingLanguage(): self
    {
        $this->getHeaders()->addTextHeader('X-MJ-TemplateLanguage', '1');

        return $this;
    }
}
