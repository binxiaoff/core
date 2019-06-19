<?php

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Traits\ConstantsAwareTrait;

trait RoleableTrait
{
    use ConstantsAwareTrait;

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

        if (defined('self::DEFAULT_ROLE')) {
            $roles[] = self::DEFAULT_ROLE;
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
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * Reset roles.
     */
    public function resetRoles()
    {
        $this->roles = [];
    }

    /**
     * @param string $role
     *
     * @return self
     */
    private function removeRole(string $role): self
    {
        $index = array_search($role, $this->roles, true);

        if (false !== $index) {
            unset($this->roles[$index]);
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getAllRoles()
    {
        return self::getConstants('ROLE_');
    }

    /**
     * @param array $roles
     *
     * @return array
     */
    private function filterRoles(array $roles): array
    {
        foreach ($roles as $index => $role) {
            if (false === in_array($role, $this->getAllRoles())) {
                unset($roles[$index]);
            }
        }

        return $roles;
    }
}
