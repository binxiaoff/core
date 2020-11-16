<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\InterestReplyVersion;
use Unilend\Entity\InvitationReplyVersion;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Repository\ClientsRepository;

class ParticipationReplyListener
{
    /** @var Security */
    private Security $security;

    /** @var ClientsRepository */
    private ClientsRepository $clientsRepository;

    public const INTEREST_REPLY_PROPERTY   = 'interestReply.money.amount';
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
            if (
                $entity instanceof ProjectParticipation && array_key_exists(self::INTEREST_REPLY_PROPERTY, $uow->getEntityChangeSet($entity))
                || $entity instanceof ProjectParticipationTranche && array_key_exists(self::INVITATION_REPLY_PROPERTY, $uow->getEntityChangeSet($entity))
            ) {
                $entityClass = get_class($entity);
                /** @var Clients $user */
                $user = $this->security->getUser();

                if ($user instanceof UserInterface && false === $user instanceof Clients) {
                    $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
                }

                $version = null;

                switch ($entityClass) {
                    case ProjectParticipation::class:
                        $version = new InterestReplyVersion($entity, $user->getCurrentStaff());
                        break;
                    case ProjectParticipationTranche::class:
                        $version = new InvitationReplyVersion($entity, $user->getCurrentStaff());
                        break;
                }

                $em->persist($version);
                $uow->computeChangeSet($em->getClassMetadata(get_class($version)), $version);
            }
        }
    }
}
