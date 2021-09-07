<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Model\Bitmask;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;

trait PermissionTrait
{
    use ConstantsAwareTrait;

    /**
     * @ORM\Column(type="bitmask")
     *
     * @Groups({"permission:read", "permission:write"})
     */
    private Bitmask $permissions;

    public function getPermissions(): Bitmask
    {
        return $this->permissions;
    }

    /**
     * @param mixed $permissions
     */
    public function setPermissions($permissions): self
    {
        $this->permissions = new Bitmask($permissions);

        return $this;
    }

    /**
     * @param $permissions
     *
     * @return $this
     */
    public function addPermission($permissions): self
    {
        $this->permissions->add($permissions);

        return $this;
    }

    /**
     * @return array|int[]
     */
    public function getAvailablePermissions(): array
    {
        return static::getConstants('PERMISSION_');
    }
}
