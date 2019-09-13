<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\ClientsStatus;
use Unilend\Entity\Invitation;
use Unilend\Entity\ProjectStatusHistory;
use Unilend\Entity\TemporaryLinksLogin;
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\ClientsStatusRepository;
use Unilend\Repository\CompaniesRepository;
use Unilend\Repository\InvitationRepository;
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
    /** @var InvitationRepository */
    private $invitationRepository;

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
     * @param InvitationRepository           $invitationRepository
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
        InvitationRepository $invitationRepository
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
        $this->invitationRepository           = $invitationRepository;
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

        $invitation = (new Invitation())
            ->setIdClient($guest)
            ->setInvitedBy($inviter)
            ->setStatus(Invitation::STATUS_SENT)
            ;
        $this->invitationRepository->save($invitation);

        $projectStatusHistory = $this->projectStatusHistoryRepository->findOneBy(['project' => $project]);

        if (ProjectStatusHistory::STATUS_PUBLISHED === $projectStatusHistory->getStatus()) {
            if (ClientsStatus::STATUS_VALIDATED === $guest->getIdClientStatusHistory()->getIdStatus()->getId()) {
                $this->mailerManager->sendProjectPublication($project, $guest);
            } elseif (ClientsStatus::STATUS_CREATION === $guest->getIdClientStatusHistory()->getIdStatus()->getId()) {
                $inviterName = $inviter->getLastName() . ' ' . $inviter->getFirstName();
                $token       = $this->temporaryLinksLoginRepository->generateTemporaryLink($guest, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_LONG);
                $this->mailerManager->sendProjectInvitation($inviterName, $token, $guest->getEmail());
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
