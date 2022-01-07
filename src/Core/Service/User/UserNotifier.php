<?php

declare(strict_types=1);

namespace KLS\Core\Service\User;

use KLS\Core\Entity\User;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Service\TemporaryTokenGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UserNotifier
{
    private RouterInterface         $router;
    private MailerInterface         $mailer;
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    private LoggerInterface         $logger;

    public function __construct(
        RouterInterface $router,
        MailerInterface $mailer,
        TemporaryTokenGenerator $temporaryTokenGenerator,
        LoggerInterface $logger
    ) {
        $this->router                  = $router;
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->logger                  = $logger;
    }

    public function notifyPasswordRequest(User $user): int
    {
        if (false === $user->isGrantedLogin() || $user->isInitializationNeeded()) {
            return 0;
        }
        $templateId = MailjetMessage::TEMPLATE_USER_PASSWORD_REQUEST;

        try {
            $temporaryToken = $this->temporaryTokenGenerator->generateMediumToken($user);

            $message = (new MailjetMessage())
                ->to($user->getEmail())
                ->setTemplateId($templateId)
                ->setVars([
                    'firstName'        => $user->getFirstName() ?? '',
                    'resetPasswordURL' => $this->router->generate(
                        'front_resetPassword',
                        [
                            'temporaryTokenPublicId' => $temporaryToken->getToken(),
                            'userPublicId'           => $user->getPublicId(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ])
            ;

            $this->mailer->send($message);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Email sending failed for %s with template id %d. Error: %s',
                    $user->getEmail(),
                    $templateId,
                    $throwable->getMessage()
                ),
                ['throwable' => $throwable]
            );

            return 0;
        }

        return 1;
    }
}
