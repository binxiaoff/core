<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Exception;
use KLS\Core\Entity\CompanyModule;
use KLS\Core\Entity\CompanyModuleLog;
use KLS\Core\Entity\User;
use Symfony\Component\Security\Core\Security;

/**
 * TODO Refactor because we should not use doctrine for automatic insert of log.
 */
class CompanyModuleUpdatedListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        // Necessary to refetch updatedBy Here because preUpdate is after onFlush
        // TODO This code should be delete in CALS-2359
        $user         = $this->security->getUser();
        $currentStaff = $user instanceof User ? $user->getCurrentStaff() : null;
        $em           = $args->getEntityManager();
        $uow          = $em->getUnitOfWork();

        $classMetadata = $em->getClassMetadata(CompanyModuleLog::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (($entity instanceof CompanyModule)) {
                $em  = $args->getEntityManager();
                $uow = $em->getUnitOfWork();
                $entity->setUpdatedBy($currentStaff);
                $log = new CompanyModuleLog($entity);
                $em->persist($log);
                $uow->computeChangeSet($classMetadata, $log);
            }
        }
    }
}
