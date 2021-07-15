<?php

declare(strict_types=1);

namespace Unilend\Core\SwiftMailer;

use InvalidArgumentException;
use JsonException;
use Unilend\Core\Traits\ConstantsAwareTrait;

class MailjetMessage extends \Swift_Message
{
    use ConstantsAwareTrait;

    public const TEMPLATE_AGENCY_AGENT_MEMBER_PROJECT_PUBLISHED            = 3024626;
    public const TEMPLATE_AGENCY_BORROWER_MEMBER_PROJECT_PUBLISHED         = 3011629;
    public const TEMPLATE_AGENCY_PARTICIPATION_MEMBER_PROJECT_PUBLISHED    = 3024637;
    public const TEMPLATE_AGENCY_REMIND_TERM_AGENT                         = 3011644;
    public const TEMPLATE_AGENCY_REMIND_TERM_BORROWER                      = 3011644;
    public const TEMPLATE_ARRANGER_INVITATION_EXTERNAL_BANK                = 1853530;
    public const TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_CASA = 3041686;
    public const TEMPLATE_MESSAGE_UNREAD_USER_NOTIFICATION                 = 2154702;
    public const TEMPLATE_PARTICIPANT_REPLY                                = 1853502;
    public const TEMPLATE_PROJECT_FILE_UPLOADED                            = 1853491;
    public const TEMPLATE_PUBLICATION                                      = 1853426;
    public const TEMPLATE_PUBLICATION_PROSPECT_COMPANY                     = 1852083;
    public const TEMPLATE_PUBLICATION_UNINITIALIZED_USER                   = 1852104;
    public const TEMPLATE_STAFF_USER_INITIALISATION                        = 1851115;
    public const TEMPLATE_SYNDICATION                                      = 1853479;
    public const TEMPLATE_SYNDICATION_PROSPECT_COMPANY                     = 1853443;
    public const TEMPLATE_SYNDICATION_UNINITIALIZED_USER                   = 1853467;
    public const TEMPLATE_USER_PASSWORD_REQUEST                            = 1852070;

    public function __construct(?string $subject = null, ?string $body = null, ?string $contentType = null, ?string $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);

        // This address is required to respect the SMTP RFC but it is not used by mailjet (set in Mailjet template)
        // This email and name are defined on MailJet template too and should be the same.
        // If email domain defined here is not the same as template sender, template email and name are used.
        // If email domain defined here is the same as template, email and name defined below are used.
        $this->setFrom('support@kls-platform.com', 'KLS');
        $this->enableTemplatingLanguage();
    }

    public function getTemplateId(): ?int
    {
        $header = $this->getHeaders()->get('X-MJ-TemplateID');

        return $header ? $header->getFieldBodyModel() : null;
    }

    public function setTemplateId(?int $templateId): MailjetMessage
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
     * @throws JsonException
     */
    public function setVars(array $vars = []): MailjetMessage
    {
        $vars = $this->filterVars($vars);

        $this->getHeaders()->addTextHeader('X-MJ-Vars', \json_encode($vars, JSON_THROW_ON_ERROR));

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function getVars(): array
    {
        $vars = $this->getHeaders()->get('X-MJ-Vars');

        return $vars ? \json_decode($vars->getFieldBody(), true, 512, JSON_THROW_ON_ERROR) : [];
    }

    public function setTemplateErrorEmail(?string $email): MailjetMessage
    {
        // Remove the previously existing headers to prevent the duplication.
        $this->getHeaders()->removeAll('X-MJ-TemplateErrorReporting');
        if ($email) {
            $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorReporting', $email);
        }

        return $this;
    }

    public function enableErrorDelivery(): MailjetMessage
    {
        // Remove the previously existing headers to prevent the duplication.
        $this->getHeaders()->removeAll('X-MJ-TemplateErrorDeliver');
        $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorDeliver', 'deliver');

        return $this;
    }

    public function disableErrorDelivery(): MailjetMessage
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

    private function enableTemplatingLanguage(): MailjetMessage
    {
        $this->getHeaders()->addTextHeader('X-MJ-TemplateLanguage', '1');

        return $this;
    }

    private function filterVars(array $vars): array
    {
        // MailJet do not let var with null value, empty value has to be false instead
        \array_walk_recursive($vars, function (&$value) {
            $value = $value ?? false;
        });

        return $vars;
    }
}
