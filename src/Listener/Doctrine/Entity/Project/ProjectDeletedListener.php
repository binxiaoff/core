<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\Project;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;
use Unilend\Exception\Staff\StaffNotFoundException;

class ProjectDeletedListener
{
    /** @var Security */
    private $security;

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
     * @throws ORMException
     * @throws StaffNotFoundException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em   = $args->getEntityManager();
        $uow  = $em->getUnitOfWork();
        $user = $this->security->getUser();

        if ((false === ($user instanceof Clients))) {
            throw new StaffNotFoundException('Client is not connected');
        }

        $staff = $user->getCurrentStaff();

        $projectClassMetadata  = $em->getClassMetadata(Project::class);
        $projectStatusMetadata = $em->getClassMetadata(ProjectStatus::class);

        $scheduledDeletetion = $uow->getScheduledEntityDeletions();
        foreach ($scheduledDeletetion as $object) {
            if ($object instanceof Project && ProjectStatus::STATUS_REQUESTED !== $object->getCurrentStatus()->getStatus()) {
                if (null === $staff) {
                    throw new StaffNotFoundException('Client is not connected with available staff');
                }
                $archivedStatus = new ProjectStatus($object, ProjectStatus::STATUS_CANCELED, $staff);
                $object->setCurrentStatus($archivedStatus);
                $em->persist($object);
                $uow->recomputeSingleEntityChangeSet($projectClassMetadata, $object);
                $uow->computeChangeSet($projectStatusMetadata, $archivedStatus);
            }
        }
    }
}
