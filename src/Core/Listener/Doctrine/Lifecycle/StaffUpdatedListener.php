<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\{StaffLog};

/**
 * TODO Refactor because we should not use doctrine for automatic insert of log
 * This one could be done with messenger
 */
class StaffUpdatedListener
{
    /** @var Security */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $user = $this->security->getUser();
        $addedBy  = $user instanceof User ? $user->getCurrentStaff() : null;
        $classMetadata = $em->getClassMetadata(StaffLog::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Staff && false === $entity->isArchived()) {
                $log = new StaffLog($entity, $addedBy);
                $em->persist($log);
                $uow->computeChangeSet($classMetadata, $log);
            }
        }
    }
}
