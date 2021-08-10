<?php

declare(strict_types=1);

namespace KLS\Core\Service\User;

use Exception;
use KLS\Core\Entity\User;
use KLS\Core\Service\TemporaryTokenGenerator;
use KLS\Core\SwiftMailer\MailjetMessage;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;

class UserNotifier
{
    private RouterInterface $router;
    private Swift_Mailer $mailer;
    private TemporaryTokenGenerator $temporaryTokenGenerator;

    public function __construct(RouterInterface $router, Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator)
    {
        $this->router                  = $router;
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @throws Exception
     */
    public function notifyPasswordRequest(User $user): void
    {
        if (false === $user->isGrantedLogin() || $user->isInitializationNeeded()) {
            return;
        }

        $temporaryToken = $this->temporaryTokenGenerator->generateMediumToken($user);

        $message = (new MailjetMessage())
            ->setTo($user->getEmail())
            ->setTemplateId(MailjetMessage::TEMPLATE_USER_PASSWORD_REQUEST)
            ->setVars([
                'firstName'        => $user->getFirstName() ?? '',
                'resetPasswordURL' => $this->router->generate(
                    'front_resetPassword',
                    [
                        'temporaryTokenPublicId' => $temporaryToken->getToken(),
                        'userPublicId'           => $user->getPublicId(),
                    ],
                    RouterInterface::ABSOLUTE_URL
                ),
            ])
        ;

        $this->mailer->send($message);
    }
}
