<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\Staff;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Staff, StaffLog};

class StaffListener
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
        $addedBy  = $user instanceof Clients ? $user->getCurrentStaff() : null;
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
