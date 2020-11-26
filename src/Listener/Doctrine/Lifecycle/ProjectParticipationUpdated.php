<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Repository\ClientsRepository;
use Unilend\Entity\InterestReplyVersion;
use Unilend\Entity\ProjectParticipation;

class ProjectParticipationUpdated
{
    /** @var Security */
    private Security $security;

    /** @var ClientsRepository */
    private ClientsRepository $clientsRepository;

    public const INTEREST_REPLY_PROPERTY = 'interestReply.money.amount';

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
            if ($entity instanceof ProjectParticipation && array_key_exists(self::INTEREST_REPLY_PROPERTY, $uow->getEntityChangeSet($entity))) {
                /** @var Clients $user */
                $user = $this->security->getUser();

                if ($user instanceof UserInterface && false === $user instanceof Clients) {
                    $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
                }

                $version = new InterestReplyVersion($entity, $user->getCurrentStaff());
                $em->persist($version);
                $uow->computeChangeSet($em->getClassMetadata(InterestReplyVersion::class), $version);
            }
        }
    }
}
