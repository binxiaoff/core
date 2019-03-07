<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait Roleable
{
    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        if (defined('self::ROLE_DEFAULT')) {
            $roles[] = self::ROLE_DEFAULT;
        }

        return array_unique($roles);
    }

    /**
     * @param array $roles
     *
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $this->filterRoles($roles);

        return $this;
    }

    /**
     * @param array $roles
     *
     * @return self
     */
    public function addRoles(array $roles): self
    {
        $this->roles = array_unique(array_merge($this->roles, $this->filterRoles($roles)));

        return $this;
    }

    /**
     * @param array $roles
     *
     * @return array
     */
    private function filterRoles(array $roles): array
    {
        foreach ($roles as $index => $role) {
            if (false === in_array($role, self::ALL_ROLES)) {
                unset($roles[$index]);
            }
        }

        return $roles;
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function resetRoles()
    {
        $this->roles = [];
    }
}
