<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\CompanyModuleLog;
use Unilend\Core\Entity\User;

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
