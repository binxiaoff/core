<?php

declare(strict_types=1);

namespace KLS\Core\Mailer;

use InvalidArgumentException;
use JsonException;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Mime\Email;

class MailjetMessage extends Email implements TraceableEmailInterface
{
    use ConstantsAwareTrait;

    public const TEMPLATE_AGENCY_AGENT_MEMBER_PROJECT_PUBLISHED               = 3024626;
    public const TEMPLATE_AGENCY_BORROWER_MEMBER_PROJECT_PUBLISHED            = 3011629;
    public const TEMPLATE_AGENCY_PARTICIPATION_MEMBER_PROJECT_PUBLISHED       = 3024637;
    public const TEMPLATE_AGENCY_REMIND_TERM_AGENT                            = 3011644;
    public const TEMPLATE_AGENCY_REMIND_TERM_BORROWER                         = 3011644;
    public const TEMPLATE_ARRANGER_INVITATION_EXTERNAL_BANK                   = 1853530;
    public const TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_CASA    = 3041686;
    public const TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_CR      = 3041937;
    public const TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_LIST_CR = 3049076;
    public const TEMPLATE_MESSAGE_UNREAD_USER_NOTIFICATION                    = 2154702;
    public const TEMPLATE_PARTICIPANT_REPLY                                   = 1853502;
    public const TEMPLATE_PROJECT_FILE_UPLOADED                               = 1853491;
    public const TEMPLATE_PUBLICATION                                         = 1853426;
    public const TEMPLATE_PUBLICATION_PROSPECT_COMPANY                        = 1852083;
    public const TEMPLATE_PUBLICATION_UNINITIALIZED_USER                      = 1852104;
    public const TEMPLATE_STAFF_USER_INITIALISATION                           = 1851115;
    public const TEMPLATE_SYNDICATION                                         = 1853479;
    public const TEMPLATE_SYNDICATION_PROSPECT_COMPANY                        = 1853443;
    public const TEMPLATE_SYNDICATION_UNINITIALIZED_USER                      = 1853467;
    public const TEMPLATE_USER_INITIALISATION                                 = 3325471;
    public const TEMPLATE_USER_PASSWORD_REQUEST                               = 1852070;

    private array $vars;

    public function __construct()
    {
        parent::__construct();
        $this->enableTemplatingLanguage();
        // The body is required by the Email object (see Email::ensureValidity), but we don't actually need it.
        $this->text('');
        // This address is required to respect the SMTP RFC, but it is not used by mailjet (set in Mailjet template)
        // This email and name are defined on MailJet template too and should be the same.
        // If email domain defined here is not the same as template sender, template email and name are used.
        // If email domain defined here is the same as template, email and name defined below are used.
        // As the generateMessageId() need the "from", we set it here instead of in mail.yaml
        $this->from('KLS <support@kls-platform.com>');
        // Generate in advance the message id (normally, it is generated on sending),
        // so that we can use it to update MailLog. See PreSendMailSubscriber::logMessage().
        $this->getHeaders()->addIdHeader('Message-ID', $this->generateMessageId());
    }

    public function getTemplateId(): ?int
    {
        $header = $this->getHeaders()->get('X-MJ-TemplateID');

        return $header ? (int) $header->getBody() : null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setTemplateId(?int $templateId): MailjetMessage
    {
        if ($templateId && false === \in_array($templateId, static::getAvailableTemplates(), true)) {
            throw new InvalidArgumentException('This template id does not exist');
        }

        if ($templateId) {
            $this->getHeaders()->addTextHeader('X-MJ-TemplateID', (string) $templateId);
        } else {
            $this->getHeaders()->remove('X-MJ-TemplateID');
        }

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function setVars(array $vars = []): MailjetMessage
    {
        $this->vars = $this->normalizeVars($vars);

        $this->getHeaders()->addTextHeader(
            'X-MJ-Vars',
            \json_encode($this->vars, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $this;
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function setTemplateErrorEmail(?string $email): MailjetMessage
    {
        // Remove the previously existing headers to prevent the duplication.
        $this->getHeaders()->remove('X-MJ-TemplateErrorReporting');
        if ($email) {
            $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorReporting', $email);
        }

        return $this;
    }

    public function enableErrorDelivery(): MailjetMessage
    {
        // Remove the previously existing headers to prevent the duplication.
        $this->getHeaders()->remove('X-MJ-TemplateErrorDeliver');
        $this->getHeaders()->addTextHeader('X-MJ-TemplateErrorDeliver', 'deliver');

        return $this;
    }

    public function disableErrorDelivery(): MailjetMessage
    {
        $this->getHeaders()->remove('X-MJ-TemplateErrorDeliver');

        return $this;
    }

    public function getMessageId(): ?string
    {
        $messageIdHeader = $this->getHeaders()->get('Message-ID');

        return $messageIdHeader ? $messageIdHeader->getBodyAsString() : null;
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

    private function normalizeVars(array $values): array
    {
        // MailJet do not let var with null value, empty value has to be false instead
        foreach ($values as &$value) {
            $value = $value ?? false;
        }

        return $values;
    }
}
