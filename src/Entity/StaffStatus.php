<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="staff_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_staff_status_status"),
 *     }
 * )
 */
class StaffStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_ACTIVE   = 10;
    public const STATUS_INACTIVE = -10;
    public const STATUS_ARCHIVED = -20;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_staff", nullable=false)
     *
     * @Groups({"staffStatus:create"})
     */
    private $staff;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"staffStatus:read", "staffStatus:create"})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @param Staff $staff
     * @param int   $status
     * @param Staff $addedBy
     *
     * @throws Exception
     */
    public function __construct(Staff $staff, int $status, Staff $addedBy)
    {
        if (!\in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
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
}
