<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\ClientsStatus;
use Unilend\Entity\ProjectInvitation;
use Unilend\Entity\ProjectStatusHistory;
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\ClientsStatusHistoryRepository;
use Unilend\Repository\ClientsStatusRepository;
use Unilend\Repository\CompaniesRepository;
use Unilend\Repository\ProjectInvitationRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Repository\ProjectStatusHistoryRepository;
use Unilend\Repository\TemporaryLinksLoginRepository;
use Unilend\Service\MailerManager;

class ClientInvitedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectStatusHistoryRepository */
    private $projectStatusHistoryRepository;
    /** @var ClientsStatusRepository */
    private $clientsStatusRepository;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var CompaniesRepository */
    private $companiesRepository;
    /** @var TemporaryLinksLoginRepository */
    private $temporaryLinksLoginRepository;
    /** @var MailerManager */
    private $mailerManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var ProjectInvitationRepository */
    private $projectInvitationRepository;
    /** @var ClientsStatusHistoryRepository */
    private $clientsStatusHistoryRepository;

    /**
     * @param ClientsRepository              $clientsRepository
     * @param ClientsStatusRepository        $clientsStatusRepository
     * @param ProjectRepository              $projectRepository
     * @param ProjectStatusHistoryRepository $projectStatusHistoryRepository
     * @param EntityManagerInterface         $entityManager
     * @param CompaniesRepository            $companiesRepository
     * @param TemporaryLinksLoginRepository  $temporaryLinksLoginRepository
     * @param MailerManager                  $mailerManager
     * @param LoggerInterface                $logger
     * @param ProjectInvitationRepository    $projectInvitationRepository
     * @param ClientsStatusHistoryRepository $clientsStatusHistoryRepository
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        ClientsStatusRepository $clientsStatusRepository,
        ProjectRepository $projectRepository,
        ProjectStatusHistoryRepository $projectStatusHistoryRepository,
        EntityManagerInterface $entityManager,
        CompaniesRepository $companiesRepository,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        MailerManager $mailerManager,
        LoggerInterface $logger,
        ProjectInvitationRepository $projectInvitationRepository,
        ClientsStatusHistoryRepository $clientsStatusHistoryRepository
    ) {
        $this->clientsRepository              = $clientsRepository;
        $this->projectRepository              = $projectRepository;
        $this->projectStatusHistoryRepository = $projectStatusHistoryRepository;
        $this->clientsStatusRepository        = $clientsStatusRepository;
        $this->entityManager                  = $entityManager;
        $this->companiesRepository            = $companiesRepository;
        $this->temporaryLinksLoginRepository  = $temporaryLinksLoginRepository;
        $this->mailerManager                  = $mailerManager;
        $this->logger                         = $logger;
        $this->projectInvitationRepository    = $projectInvitationRepository;
        $this->clientsStatusHistoryRepository = $clientsStatusHistoryRepository;
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

        $invitation = (new ProjectInvitation())
            ->setClient($guest)
            ->setInvitedBy($inviter)
            ->setProject($project)
            ->setStatus(ProjectInvitation::STATUS_SENT)
            ;
        $this->projectInvitationRepository->save($invitation);
        $inviterName = $inviter->getLastName() . ' ' . $inviter->getFirstName();

        $projectStatusHistory = $this->projectStatusHistoryRepository->findOneBy(['project' => $project]);
        $guestStatus          = $this->clientsStatusHistoryRepository->findActualStatus($guest)->getIdStatus()->getId();

        if (ProjectStatusHistory::STATUS_PUBLISHED === $projectStatusHistory->getStatus()) {
            if (ClientsStatus::STATUS_VALIDATED === $guestStatus) {
                $this->mailerManager->sendProjectInvitation($invitation);
            } elseif (ClientsStatus::STATUS_CREATION === $guestStatus) {
                $this->mailerManager->sendProjectInvitationNewUser($invitation);
            } else {
                $this->logger->error('Unable to retrieve last client notifications. Error: ', [
                    'id_client'     => $guest->getIdClient(),
                    'status_client' => $guest->getIdClientStatusHistory(),
                    'class'         => __CLASS__,
                    'function'      => __FUNCTION__,
                ]);
            }
        }
    }
}
