<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;

class UuidListener
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (\in_array(PublicizeIdentityTrait::class, \class_uses($entity), true)) {
            $entity->setPublicId();
        }
    }
}
