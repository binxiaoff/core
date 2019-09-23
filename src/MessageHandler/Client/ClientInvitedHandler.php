<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ProjectInvitationRepository;
use Unilend\Service\MailerManager;

class ClientInvitedHandler implements MessageHandlerInterface
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectInvitationRepository */
    private $projectInvitationRepository;

    /**
     * @param MailerManager               $mailerManager
     * @param ProjectInvitationRepository $projectInvitationRepository
     */
    public function __construct(
        MailerManager $mailerManager,
        ProjectInvitationRepository $projectInvitationRepository
    ) {
        $this->mailerManager               = $mailerManager;
        $this->projectInvitationRepository = $projectInvitationRepository;
    }

    /**
     * @param ClientInvited $clientInvited
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(ClientInvited $clientInvited)
    {
        $projectInvitation = $this->projectInvitationRepository->findOneBy(['id' => $clientInvited->getProjectInvitationId()]);

        $this->mailerManager->sendProjectInvitation($projectInvitation);
    }
}
