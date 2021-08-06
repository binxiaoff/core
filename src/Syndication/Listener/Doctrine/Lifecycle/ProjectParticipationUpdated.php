<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Syndication\Entity\InterestReplyVersion;
use Unilend\Syndication\Entity\ProjectParticipation;

class ProjectParticipationUpdated
{
    public const INTEREST_REPLY_PROPERTY = 'interestReply.money.amount';

    private Security $security;

    private UserRepository $userRepository;

    public function __construct(Security $security, UserRepository $userRepository)
    {
        $this->security       = $security;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws \Exception
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ProjectParticipation && \array_key_exists(self::INTEREST_REPLY_PROPERTY, $uow->getEntityChangeSet($entity))) {
                /** @var User $user */
                $user = $this->security->getUser();

                if ($user instanceof UserInterface && false === $user instanceof User) {
                    $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
                }

                $version = new InterestReplyVersion($entity, $user->getCurrentStaff());
                $em->persist($version);
                $uow->computeChangeSet($em->getClassMetadata(InterestReplyVersion::class), $version);
            }
        }
    }
}
