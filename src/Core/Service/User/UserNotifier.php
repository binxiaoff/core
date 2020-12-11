<?php

declare(strict_types=1);

namespace Unilend\Core\Service\User;

use Exception;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Service\TemporaryTokenGenerator;
use Unilend\Core\SwiftMailer\MailjetMessage;

class UserNotifier
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
     * @param User $user
     *
     * @throws Exception
     */
    public function notifyPasswordRequest(User $user): void
    {
        if (false === $user->isGrantedLogin() || $user->isInitializationNeeded()) {
            return;
        }

        $temporaryToken = $this->temporaryTokenGenerator->generateMediumToken($user);

        $message = (new MailjetMessage())
            ->setTemplateId(MailjetMessage::TEMPLATE_USER_PASSWORD_REQUEST)
            ->setVars([
                'firstName' => $user->getFirstName(),
                'resetPasswordURL' => $this->router->generate(
                    'front_resetPassword',
                    [
                    'temporaryTokenPublicId' => $temporaryToken->getToken(),
                    'userPublicId' => $user->getPublicId(),
                    ],
                    RouterInterface::ABSOLUTE_URL
                ),
            ])
            ->setTo($user->getEmail())
        ;

        $this->mailer->send($message);
    }
}
