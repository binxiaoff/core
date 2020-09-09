<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\CompanyModule;
use Unilend\Entity\CompanyModuleLog;

/**
 * TODO Refactor because we should not use doctrine for automatic insert of log
 */
class CompanyModuleUpdatedListener
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
        // Necessary to refetch updatedBy Here because preUpdate is after onFlush
        // TODO This code should be delete in CALS-2359
        $user = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $classMetadata = $em->getClassMetadata(CompanyModuleLog::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (($entity instanceof CompanyModule)) {
                $changeSet = $uow->getEntityChangeSet($entity);

                if (isset($changeSet['activated'])) {
                    $em = $args->getEntityManager();
                    $uow = $em->getUnitOfWork();
                    $entity->setUpdatedBy($currentStaff);
                    $log = new CompanyModuleLog($entity);
                    $em->persist($log);
                    $uow->computeChangeSet($classMetadata, $log);
                }
            }
        }
    }
}
