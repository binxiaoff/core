<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Entity\ProjectStatusHistory;
use LogicException;
use Symfony\Component\Security\Core\Security;

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

        $projectClassMetadata              = $em->getClassMetadata(Project::class);
        $projectStatusHistoryClassMetadata = $em->getClassMetadata(ProjectStatusHistory::class);

        $token = $this->security->getToken();

        $currentStaff = $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Project) {
                if (false === ($currentStaff instanceof Staff)) {
                    throw new LogicException(\sprintf('ProjectStatus history only accept %s as valid addedBy', Staff::class));
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
                        throw new LogicException(\sprintf('ProjectStatus history only accept %s as valid addedBy', Staff::class));
                    }

                    $projectStatusHistory = new ProjectStatusHistory($entity, $currentStaff);

                    $em->persist($projectStatusHistory);

                    $uow->computeChangeSet($projectStatusHistoryClassMetadata, $projectStatusHistory);
                }
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Project && false === $entity->isDraft()) {
                if (false === ($currentStaff instanceof Staff)) {
                    throw new LogicException(\sprintf('ProjectStatus history only accept %s as valid addedBy', Staff::class));
                }

                if ($entity->isPublished()) {
                    $entity->archive();

                    $projectStatusHistory = new ProjectStatusHistory($entity, $currentStaff);

                    $em->persist($projectStatusHistory);

                    $uow->computeChangeSet($projectStatusHistoryClassMetadata, $projectStatusHistory);
                }

                $em->persist($entity);
                $uow->computeChangeSet($projectClassMetadata, $entity);
            }
        }
    }
}
