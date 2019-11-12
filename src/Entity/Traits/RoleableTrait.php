<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
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
        return array_unique($this->roles);
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
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Reset roles.
     */
    public function resetRoles(): void
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
    private function getAvailableRoles(): array
    {
        if ($this instanceof UserInterface) {
            return self::getConstants('ROLE_');
        }

        return self::getConstants('DUTY_');
    }

    /**
     * @param array $roles
     *
     * @return array
     */
    private function filterRoles(array $roles): array
    {
        $availableRoles = $this->getAvailableRoles();
        foreach ($roles as $index => $role) {
            if (false === in_array($role, $availableRoles, true)) {
                unset($roles[$index]);
            }
        }

        return $roles;
    }
}
