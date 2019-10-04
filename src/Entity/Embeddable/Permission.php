<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Permission
{
    public const PERMISSION_READ = 0b00001;
    public const PERMISSION_EDIT = 0b00010;

    private const DEFAULT_PERMISSION = self::PERMISSION_READ;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false, options={"default": 1})
     */
    private $permission;

    /**
     * @param int $permission
     */
    public function __construct(
        $permission = self::DEFAULT_PERMISSION
    ) {
        $this->permission = $permission;
    }

    /**
     * @param int $permission
     *
     * @return Permission
     */
    public function add(int $permission): Permission
    {
        $this->permission |= $permission;

        return $this;
    }

    /**
     * @param int $permission
     *
     * @return Permission
     */
    public function remove(int $permission): Permission
    {
        $this->permission &= ~$permission;

        return $this;
    }

    /**
     * @param int $permission
     *
     * @return bool
     */
    public function has(int $permission): bool
    {
        return (bool) ($this->permission & $permission);
    }

    /**
     * @param int $permission
     *
     * @return Permission
     */
    public function set(int $permission): Permission
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * @return Permission
     */
    public function reset(): Permission
    {
        $this->permission = static::DEFAULT_PERMISSION;

        return $this;
    }

    /**
     * @return int
     */
    public function get(): int
    {
        return $this->permission;
    }
}
