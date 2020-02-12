<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use LogicException;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{ClientStatus, Clients, Project, ProjectStatus, TemporaryToken};
use Unilend\Service\NotificationManager;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ClientNotifier
{
    /** @var RouterInterface */
    private $router;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param RouterInterface         $router
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     * @param NotificationManager     $notificationManager
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        RouterInterface $router,
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        NotificationManager $notificationManager,
        TranslatorInterface $translator
    ) {
        $this->router              = $router;
        $this->messageProvider     = $messageProvider;
        $this->mailer              = $mailer;
        $this->notificationManager = $notificationManager;
        $this->translator          = $translator;
    }

    /**
     * @param TemporaryToken $temporaryToken
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function notifyPasswordRequest(TemporaryToken $temporaryToken)
    {
        $client = $temporaryToken->getClient();

        if (false === $temporaryToken->isValid()) {
            throw new LogicException('The token should be valid at this point');
        }

        $message = $this->messageProvider->newMessage('client-password-request', [
            'client' => [
                'firstName' => $client->getFirstName(),
                'hash'      => $client->getPublicId(),
            ],
            'temporaryToken' => [
                'token' => $temporaryToken->getToken(),
            ],
        ])->setTo($client->getEmail());

        $this->mailer->send($message);
    }
}
