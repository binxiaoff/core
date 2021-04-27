<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use LogicException;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\ProjectStatusHistory;
use Unilend\Core\Entity\Staff;

class ProjectListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $projectStatusHistoryClassMetadata = $em->getClassMetadata(ProjectStatusHistory::class);

        $token = $this->security->getToken();

        $currentStaff = $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Project) {
                if (false === ($currentStaff instanceof Staff)) {
                    throw new LogicException(sprintf('ProjectStatus history only accept %s as valid addedBy', Staff::class));
                }

                $projectStatusHistory = new ProjectStatusHistory($entity, $currentStaff);

                $em->persist($projectStatusHistory);

                $uow->computeChangeSet($projectStatusHistoryClassMetadata, $projectStatusHistory);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Project) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (isset($changeSet['currentStatus'])) {
                    if (false === ($currentStaff instanceof Staff)) {
                        throw new LogicException(sprintf('ProjectStatus history only accept %s as valid addedBy', Staff::class));
                    }

                    $projectStatusHistory = new ProjectStatusHistory($entity, $currentStaff);

                    $em->persist($projectStatusHistory);

                    $uow->computeChangeSet($projectStatusHistoryClassMetadata, $projectStatusHistory);
                }
            }
        }
    }
}
