<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @Assert\Choice(callback="getAvailableRoles", multiple=true, multipleMessage="Core.Roleable.roles.choice")
     * @Assert\Count(min="1", minMessage="Core.Roleable.roles.count")
     * @Assert\Unique(message="Core.Roleable.roles.unique")
     */
    private $roles = [];

    public function getRoles(): array
    {
        return \array_unique($this->roles);
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles): self
    {
        $this->roles = $this->filterRoles((array) $roles);

        return $this;
    }

    public function addRoles(array $roles): self
    {
        $this->roles = \array_unique(\array_merge($this->roles, $this->filterRoles($roles)));

        return $this;
    }

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

    public static function getAvailableRoles(): array
    {
        return self::getConstants('DUTY_');
    }

    private function removeRole(string $role): self
    {
        $index = \array_search($role, $this->roles, true);

        if (false !== $index) {
            unset($this->roles[$index]);
        }

        return $this;
    }

    private function filterRoles(array $roles): array
    {
        return \array_unique(\array_values(\array_intersect($roles, static::getAvailableRoles())));
    }
}
