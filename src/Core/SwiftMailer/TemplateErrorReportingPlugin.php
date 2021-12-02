<?php

declare(strict_types=1);

namespace KLS\Core\SwiftMailer;

use Swift_Events_SendEvent;
use Swift_Events_SendListener;

class TemplateErrorReportingPlugin implements Swift_Events_SendListener
{
    private bool   $enableErrorDelivery;
    private string $errorReportingEmail;

    public function __construct(bool $enableErrorDelivery = false, ?string $errorReportingEmail = null)
    {
        $this->enableErrorDelivery = $enableErrorDelivery;
        $this->errorReportingEmail = $errorReportingEmail;
    }

    public function beforeSendPerformed(Swift_Events_SendEvent $event): void
    {
        $message = $event->getMessage();

        if ($message instanceof MailjetMessage) {
            $message->setTemplateErrorEmail($this->errorReportingEmail);

            if ($this->enableErrorDelivery) {
                $message->enableErrorDelivery();
            }
        }
    }

    public function sendPerformed(Swift_Events_SendEvent $event): void
    {
    }
}
