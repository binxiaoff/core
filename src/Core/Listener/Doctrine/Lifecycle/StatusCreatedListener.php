<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use KLS\Core\Entity\Interfaces\StatusInterface;

class StatusCreatedListener
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if ($entity instanceof StatusInterface) {
            $entity->getAttachedObject()->setCurrentStatus($entity);
        }
    }
}
