<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Exception;
use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\Clients;
use Unilend\Service\TemporaryTokenGenerator;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ClientNotifier
{
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var TemporaryTokenGenerator */
    private $temporaryTokenGenerator;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     */
    public function __construct(TemplateMessageProvider $messageProvider, Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator)
    {
        $this->messageProvider         = $messageProvider;
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @param Clients $client
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function notifyPasswordRequest(Clients $client): void
    {
        if (false === $client->isGrantedLogin() || $client->isInitializationNeeded()) {
            return;
        }

        $message = $this->messageProvider->newMessage('client-password-request', [
            'client' => [
                'firstName' => $client->getFirstName(),
                'publicId'  => $client->getPublicId(),
            ],
            'temporaryToken' => [
                'token' => $this->temporaryTokenGenerator->generateMediumToken($client)->getToken(),
            ],
        ])->setTo($client->getEmail());

        $this->mailer->send($message);
    }
}
