<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectStatus;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Unilend\Entity\ProjectStatus;

class ProjectStatusCreatedListener
{
    /**
     * @param ProjectStatus      $status
     * @param LifecycleEventArgs $args
     */
    public function setCurrentStatus(ProjectStatus $status, LifecycleEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $project = $status->getProject();

        $project->setCurrentStatus($status);
    }
}
