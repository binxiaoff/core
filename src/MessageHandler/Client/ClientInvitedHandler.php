<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientsStatusHistory;
use Unilend\Entity\ProjectParticipant;
use Unilend\Entity\Staff;
use Unilend\Entity\TemporaryLinksLogin;
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\ClientsStatusRepository;
use Unilend\Repository\CompaniesRepository;
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

    /**
     * @param ClientsRepository              $clientsRepository
     * @param ClientsStatusRepository        $clientsStatusRepository
     * @param ProjectRepository              $projectRepository
     * @param ProjectStatusHistoryRepository $projectStatusHistoryRepository
     * @param EntityManagerInterface         $entityManager
     * @param CompaniesRepository            $companiesRepository
     * @param TemporaryLinksLoginRepository  $temporaryLinksLoginRepository
     * @param MailerManager                  $mailerManager
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        ClientsStatusRepository $clientsStatusRepository,
        ProjectRepository $projectRepository,
        ProjectStatusHistoryRepository $projectStatusHistoryRepository,
        EntityManagerInterface $entityManager,
        CompaniesRepository $companiesRepository,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        MailerManager $mailerManager
    ) {
        $this->clientsRepository              = $clientsRepository;
        $this->projectRepository              = $projectRepository;
        $this->projectStatusHistoryRepository = $projectStatusHistoryRepository;
        $this->clientsStatusRepository        = $clientsStatusRepository;
        $this->entityManager                  = $entityManager;
        $this->companiesRepository            = $companiesRepository;
        $this->temporaryLinksLoginRepository  = $temporaryLinksLoginRepository;
        $this->mailerManager                  = $mailerManager;
    }

    /**
     * @param ClientInvited $clientInvited
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(ClientInvited $clientInvited)
    {
        $project       = $this->projectRepository->findOneBy(['id' => $clientInvited->getIdProject()]);
        $projectStatus = $this->projectStatusHistoryRepository->findOneBy(['project' => $project]);

        if (10 !== $projectStatus->getStatus()) {
            $inviter = $this->clientsRepository->findOneBy(['idClient' => $clientInvited->getIdInviter()]);

            $guest               = new Clients();
            $statusClient        = $this->clientsStatusRepository->findOneBy(['id' => 60]);
            $statusClientHistory = (new ClientsStatusHistory())->setIdClient($guest)->setIdStatus($statusClient);
            $this->entityManager->persist($statusClientHistory);

            $company = $this->companiesRepository->findOneBy(['emailDomain' => $clientInvited->getGuestEmailDomain()]);

            $staff = (new Staff())->setCompany($company)->setClient($guest);
            $this->entityManager->persist($staff);

            $guest
                ->setEmail($clientInvited->getGuestEmail())
                ->setIdClientStatusHistory($statusClientHistory)
                ;
            $this->clientsRepository->save($guest);

            $projectParticipant = (new ProjectParticipant())->setProject($project)->setClient($guest)->setCompany($company);
            $this->entityManager->persist($projectParticipant);
            $this->entityManager->flush();

            $inviterName = $inviter->getLastName() . ' ' . $inviter->getFirstName();
            $token       = $this->temporaryLinksLoginRepository->generateTemporaryLink($guest, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_LONG);
            $guestEmail  = $clientInvited->getGuestEmail();

            $this->mailerManager->sendInvitation($inviterName, $token, $guestEmail);
        }
    }
}
