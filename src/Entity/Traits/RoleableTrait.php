<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Traits\ConstantsAwareTrait;

trait RoleableTrait
{
    use ConstantsAwareTrait;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     *
     * @Groups({"role:read", "role:write"})
     *
     * @Assert\Choice(callback="getAvailableRoles", multiple=true, multipleMessage="Roleable.roles.choice")
     * @Assert\Count(min="1", minMessage="Roleable.roles.count")
     * @Assert\Unique(message="Roleable.roles.unique")
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
    public function setRoles($roles): self
    {
        $this->roles = $this->filterRoles((array) $roles);

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
        return \in_array($role, $this->getRoles(), true);
    }

    /**
     * Reset roles.
     */
    public function resetRoles(): void
    {
        $this->roles = [];
    }

    /**
     * @return array
     */
    public static function getAvailableRoles(): array
    {
        return self::getConstants('DUTY_');
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
     * @param array $roles
     *
     * @return array
     */
    private function filterRoles(array $roles): array
    {
        return array_unique(array_values(array_intersect($roles, static::getAvailableRoles())));
    }
}
