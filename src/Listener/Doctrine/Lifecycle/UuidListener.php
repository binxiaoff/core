<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

class UuidListener
{
    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (\in_array(PublicizeIdentityTrait::class, class_uses($entity), true)) {
            $entity->setPublicId();
        }
    }
}
