<?php

declare(strict_types=1);

namespace Unilend\MessageHandler;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Swift_Mailer;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\Messenger\ResetPassword;
use Unilend\Entity\TemporaryToken;
use Unilend\Repository\ClientsRepository;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ResetPasswordHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var string */
    private $frontUrl;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var ObjectManager */
    private $manager;

    /**
     * @param ClientsRepository       $clientsRepository
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     * @param ObjectManager           $manager
     * @param string                  $frontUrl
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        ObjectManager $manager,
        string $frontUrl
    ) {
        $this->clientsRepository = $clientsRepository;
        $this->messageProvider   = $messageProvider;
        $this->frontUrl          = $frontUrl;
        $this->mailer            = $mailer;
        $this->manager           = $manager;
    }

    /**
     * @param ResetPassword $resetPasswordRequest
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function __invoke(ResetPassword $resetPasswordRequest): void
    {
        $clients = $this->clientsRepository->findOneBy(['email' => $resetPasswordRequest->email]);

        if (!$clients) {
            return;
        }

        $token = TemporaryToken::generateShortToken($clients);

        $this->manager->persist($token);
        $this->manager->flush();

        $requestData = get_object_vars($resetPasswordRequest);
        unset($requestData['email']);

        $message = $this->messageProvider->newMessage('forgotten-password', [
            'firstName'          => $clients->getFirstName(),
            'email'              => $clients->getEmail(),
            'passwordLink'       => implode(DIRECTORY_SEPARATOR, [rtrim($this->frontUrl, DIRECTORY_SEPARATOR), 'password', 'change', $token->getToken()]),
            'cancelPasswordLink' => implode(DIRECTORY_SEPARATOR, [rtrim($this->frontUrl, DIRECTORY_SEPARATOR), 'password', 'change', $token->getToken(), 'cancel']),
            'requesterData'      => $requestData,
        ]);

        $this->mailer->send($message);
    }
}
