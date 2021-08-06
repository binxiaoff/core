<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\User;

class UpdatedByListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        /** @var User $user */
        $user = $this->security->getUser();

        $currentStaff = $user instanceof User ? $user->getCurrentStaff() : null;

        if (\method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($currentStaff);
        }
    }
}
