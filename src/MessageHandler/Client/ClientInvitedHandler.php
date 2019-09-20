<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\ProjectInvitation;
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\ProjectInvitationRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\MailerManager;

class ClientInvitedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectInvitationRepository */
    private $projectInvitationRepository;

    /**
     * @param ClientsRepository           $clientsRepository
     * @param ProjectRepository           $projectRepository
     * @param MailerManager               $mailerManager
     * @param ProjectInvitationRepository $projectInvitationRepository
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        ProjectRepository $projectRepository,
        MailerManager $mailerManager,
        ProjectInvitationRepository $projectInvitationRepository
    ) {
        $this->clientsRepository           = $clientsRepository;
        $this->projectRepository           = $projectRepository;
        $this->mailerManager               = $mailerManager;
        $this->projectInvitationRepository = $projectInvitationRepository;
    }

    /**
     * @param ClientInvited $clientInvited
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Swift_RfcComplianceException
     */
    public function __invoke(ClientInvited $clientInvited)
    {
        $guest   = $this->clientsRepository->findOneBy(['idClient' => $clientInvited->getGuestId()]);
        $inviter = $this->clientsRepository->findOneBy(['idClient' => $clientInvited->getInviterId()]);
        $project = $this->projectRepository->findOneBy(['id' => $clientInvited->getProjectId()]);

        $projectInvitation = (new ProjectInvitation())
            ->setClient($guest)
            ->setInvitedBy($inviter)
            ->setProject($project)
            ->setStatus(ProjectInvitation::STATUS_SENT)
            ;
        $this->projectInvitationRepository->save($projectInvitation);

        $this->mailerManager->sendProjectInvitation($projectInvitation);
    }
}
