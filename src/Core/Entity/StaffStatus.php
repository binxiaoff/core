<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableAddedOnlyTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     attributes={
 *         "route_prefix"="/core"
 *     },
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
 *     callback={"Unilend\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
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
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_staff", nullable=false)
     *
     * @Groups({"staffStatus:create"})
     */
    private Staff $staff;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     *
     * @Groups({"staffStatus:read", "staffStatus:create"})
     */
    private int $status;

    /**
     * @param Staff $staff
     * @param int   $status
     * @param Staff $addedBy
     *
     * @throws Exception
     */
    public function __construct(Staff $staff, int $status, Staff $addedBy)
    {
        $this->staff   = $staff;
        $this->status  = $status;
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();
    }

    /**
     * @return Staff
     */
    public function getStaff(): Staff
    {
        return $this->staff;
    }

    /**
     * @return int
     */
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
