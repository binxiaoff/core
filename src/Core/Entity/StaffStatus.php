<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"staffStatus:read"}},
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"staffStatus:create"}},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_staff_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_staff_status_status")
 *     }
 * )
 *
 * @Assert\Callback(
 *     callback={"KLS\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status" }
 * )
 */
class StaffStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    public const STATUS_ACTIVE   = 10;
    public const STATUS_INACTIVE = -10;
    public const STATUS_ARCHIVED = -20;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_staff", nullable=false)
     *
     * @Groups({"staffStatus:create"})
     */
    private Staff $staff;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     *
     * @Groups({"staffStatus:read", "staffStatus:create"})
     */
    private int $status;

    /**
     * @throws Exception
     */
    public function __construct(Staff $staff, int $status, Staff $addedBy)
    {
        $this->staff   = $staff;
        $this->status  = $status;
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();
    }

    public function getStaff(): Staff
    {
        return $this->staff;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array|string[]
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * @return TraceableStatusAwareInterface|Staff
     */
    public function getAttachedObject()
    {
        return $this->getStaff();
    }
}
