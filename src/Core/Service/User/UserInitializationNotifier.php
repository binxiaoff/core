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

class UserInitializationNotifier
{
    private RouterInterface         $router;
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    private MailerInterface         $mailer;
    private LoggerInterface         $logger;

    public function __construct(
        RouterInterface $router,
        TemporaryTokenGenerator $temporaryTokenGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->router                  = $router;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->mailer                  = $mailer;
        $this->logger                  = $logger;
    }

    public function notifyUserInitialization(User $user): int
    {
        if (false === $user->isInitializationNeeded() || false === $user->isGrantedLogin()) {
            return 0;
        }

        try {
            $templateId = MailjetMessage::TEMPLATE_USER_INITIALISATION;
            $token      = $this->temporaryTokenGenerator->generateUltraLongToken($user)->getToken();

            $message = (new MailjetMessage())
                ->to($user->getEmail())
                ->setTemplateId($templateId)
                ->setVars([
                    'inscriptionFinalisationUrl' => $this->router->generate(
                        'front_initialAccount',
                        ['temporaryTokenPublicId' => $token, 'userPublicId' => $user->getPublicId()],
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
