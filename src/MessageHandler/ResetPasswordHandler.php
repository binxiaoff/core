<?php

declare(strict_types=1);

namespace Unilend\MessageHandler;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Swift_Mailer;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\Request\ResetPassword;
use Unilend\Entity\TemporaryToken;
use Unilend\Repository\ClientsRepository;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ResetPasswordHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var ObjectManager */
    private $manager;
    /** @var RouterInterface */
    private $router;

    /**
     * @param ClientsRepository       $clientsRepository
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     * @param ObjectManager           $manager
     * @param RouterInterface         $router
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        ObjectManager $manager,
        RouterInterface $router
    ) {
        $this->clientsRepository = $clientsRepository;
        $this->messageProvider   = $messageProvider;
        $this->router            = $router;
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

        if (false === $clients->isGrantedLogin()) {
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
            'passwordLink'       => $this->router->generate('front_password_change', ['token' => $token->getToken()], RouterInterface::ABSOLUTE_URL),
            'cancelPasswordLink' => '', // Doesn't exist for now
            'requesterData'      => $requestData,
        ]);
        $message->setTo($clients->getEmail());

        $this->mailer->send($message);
    }
}
