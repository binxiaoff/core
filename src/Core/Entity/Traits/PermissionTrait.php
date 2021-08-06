<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Model\Bitmask;
use Unilend\Core\Traits\ConstantsAwareTrait;

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
