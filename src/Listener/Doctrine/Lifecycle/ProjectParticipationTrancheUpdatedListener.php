<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Repository\ClientsRepository;
use Unilend\Entity\InvitationReplyVersion;
use Unilend\Entity\ProjectParticipationTranche;

class ProjectParticipationTrancheUpdatedListener
{
    /** @var Security */
    private Security $security;

    /** @var ClientsRepository */
    private ClientsRepository $clientsRepository;

    public array $idsProjectParticipations = [];
    public const INVITATION_REPLY_PROPERTY = 'invitationReply.money.amount';

    /**
     * @param Security          $security
     * @param ClientsRepository $clientsRepository
     */
    public function __construct(Security $security, ClientsRepository $clientsRepository)
    {
        $this->security          = $security;
        $this->clientsRepository = $clientsRepository;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws \Exception
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ProjectParticipationTranche && array_key_exists(self::INVITATION_REPLY_PROPERTY, $uow->getEntityChangeSet($entity))) {
                /** @var Clients $user */
                $user = $this->security->getUser();

                if ($user instanceof UserInterface && false === $user instanceof Clients) {
                    $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
                }

                $projectParticipation = $entity->getProjectParticipation();

                if (!in_array($projectParticipation->getPublicId(), $this->idsProjectParticipations)) {
                    foreach ($projectParticipation->getProjectParticipationTranches() as $projectParticipationTranche) {
                        $version = new InvitationReplyVersion($projectParticipationTranche, $user->getCurrentStaff());
                        $em->persist($version);
                        $uow->computeChangeSet($em->getClassMetadata(InvitationReplyVersion::class), $version);
                    }
                    $this->idsProjectParticipations[] = $projectParticipation->getPublicId();
                }
            }
        }
    }
}
