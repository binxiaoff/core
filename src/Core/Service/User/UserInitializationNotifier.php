<?php

declare(strict_types=1);

namespace KLS\Core\Service\User;

use KLS\Core\Entity\User;
use KLS\Core\Service\TemporaryTokenGenerator;
use KLS\Core\SwiftMailer\MailjetMessage;
use Swift_Mailer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UserInitializationNotifier
{
    private RouterInterface $router;
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    private Swift_Mailer $mailer;

    public function __construct(
        RouterInterface $router,
        TemporaryTokenGenerator $temporaryTokenGenerator,
        Swift_Mailer $mailer
    ) {
        $this->router                  = $router;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->mailer                  = $mailer;
    }

    public function notifyUserInitialization(User $user): int
    {
        if (false === $user->isInitializationNeeded() || false === $user->isGrantedLogin()) {
            return 0;
        }

        $token = $this->temporaryTokenGenerator->generateUltraLongToken($user)->getToken();

        $message = (new MailjetMessage())
            ->setTo($user->getEmail())
            ->setTemplateId(MailjetMessage::TEMPLATE_USER_INITIALISATION)
            ->setVars([
                'inscriptionFinalisationUrl' => $this->router->generate(
                    'front_initialAccount',
                    ['temporaryTokenPublicId' => $token, 'userPublicId' => $user->getPublicId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])
        ;

        return $this->mailer->send($message);
    }
}
