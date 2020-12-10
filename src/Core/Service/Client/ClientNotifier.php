<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Client;

use Exception;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Service\TemporaryTokenGenerator;
use Unilend\Core\SwiftMailer\MailjetMessage;

class ClientNotifier
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;
    /** @var TemporaryTokenGenerator */
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    /** @var RouterInterface */
    private RouterInterface $router;

    /**
     * @param Swift_Mailer            $mailer
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     * @param RouterInterface         $router
     */
    public function __construct(Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator, RouterInterface $router)
    {
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->router = $router;
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

        $temporaryToken = $this->temporaryTokenGenerator->generateMediumToken($client);

        $message = (new MailjetMessage())
            ->setTemplateId(MailjetMessage::TEMPLATE_CLIENT_PASSWORD_REQUEST)
            ->setVars([
                'firstName' => $client->getFirstName(),
                'resetPasswordURL' => $this->router->generate(
                    'front_resetPassword',
                    [
                    'temporaryTokenPublicId' => $temporaryToken->getToken(),
                    'clientPublicId' => $client->getPublicId(),
                    ],
                    RouterInterface::ABSOLUTE_URL
                ),
            ])
            ->setTo($client->getEmail())
        ;

        $this->mailer->send($message);
    }
}
