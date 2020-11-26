<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Unilend\Core\Entity\Interfaces\StatusInterface;

class StatusCreatedListener
{
    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if ($entity instanceof StatusInterface) {
            $entity->getAttachedObject()->setCurrentStatus($entity);
        }
    }
}
