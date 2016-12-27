<?php

namespace Unilend\Bundle\FrontBundle\Security;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ClientRole
{
    /** @var  RoleHierarchyInterface */
    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    public function isGranted($role, UserInterface $user)
    {
        $role = new Role($role);

        foreach ($user->getRoles() as $userRole) {
            if (true === in_array($role, $this->roleHierarchy->getReachableRoles([new Role($userRole)]))) {
                return true;
            }
        }

        return false;
    }
}