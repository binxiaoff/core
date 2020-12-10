<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Clients;

class UpdatedByListener
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
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        /** @var Clients $user */
        $user = $this->security->getUser();

        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($currentStaff);
        }
    }
}
