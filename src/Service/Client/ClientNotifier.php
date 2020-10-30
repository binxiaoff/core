<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Exception;
use Swift_Mailer;
use Unilend\Entity\Clients;
use Unilend\Service\TemporaryTokenGenerator;
use Unilend\SwiftMailer\MailjetMessage;

class ClientNotifier
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;
    /** @var TemporaryTokenGenerator */
    private TemporaryTokenGenerator $temporaryTokenGenerator;

    /**
     * @param Swift_Mailer            $mailer
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     */
    public function __construct(Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator)
    {
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     */
    public function notifyPasswordRequest(Clients $client): void
    {
        if (false === $client->isGrantedLogin() || $client->isInitializationNeeded()) {
            return;
        }

        $message = (new MailjetMessage())
            ->setTemplateId(1817538)
            ->setVars([
                'client' => [
                    'firstName' => $client->getFirstName(),
                    'publicId'  => $client->getPublicId(),
                ],
                'temporaryToken' => [
                    'token' => $this->temporaryTokenGenerator->generateMediumToken($client)->getToken(),
                ],
            ])
            ->setTo($client->getEmail())
        ;

        $this->mailer->send($message);
    }
}
