<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
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
        $guest   = $this->clientsRepository->findOneBy(['idClient' => $clientInvited->getGuestId()]);
        $inviter = $this->clientsRepository->findOneBy(['idClient' => $clientInvited->getInviterId()]);

        $inviterName = $inviter->getLastName() . ' ' . $inviter->getFirstName();
        $token       = $this->temporaryLinksLoginRepository->generateTemporaryLink($guest, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_LONG);

        $this->mailerManager->sendInvitation($inviterName, $token, $guest->getEmail());
    }
}
