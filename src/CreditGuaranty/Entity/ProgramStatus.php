<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\StatusInterface;
use Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:programStatus:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:programStatus:write"}},
 *     collectionOperations={
 *         "post"
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *     }
 * )
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program_status")
 *
 * @Assert\Callback(
 *     callback={"Unilend\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status", "allowedStatus": self::ALLOWED_STATUS }
 * )
 */
class ProgramStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    public const STATUS_CANCELLED   = -10;
    public const STATUS_DRAFT       = 10;
    public const STATUS_DISTRIBUTED = 20;
    public const STATUS_PAUSED      = 30;

    public const ALLOWED_STATUS = [
        self::STATUS_CANCELLED   => [],
        self::STATUS_DRAFT       => [self::STATUS_CANCELLED, self::STATUS_DISTRIBUTED],
        self::STATUS_DISTRIBUTED => [self::STATUS_CANCELLED, self::STATUS_PAUSED],
        self::STATUS_PAUSED      => [self::STATUS_CANCELLED, self::STATUS_DISTRIBUTED],
    ];

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_program", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"creditGuaranty:programStatus:read", "creditGuaranty:programStatus:write"})
     */
    private Program $program;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     *
     * @Groups({"creditGuaranty:programStatus:read", "creditGuaranty:programStatus:write"})
     */
    private int $status;

    /**
     * @param Program $program
     * @param int     $status
     * @param Staff   $addedBy
     */
    public function __construct(Program $program, int $status, Staff $addedBy)
    {
        $this->program = $program;
        $this->status  = $status;
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * @return Program|TraceableStatusAwareInterface
     */
    public function getAttachedObject()
    {
        return $this->getProgram();
    }
}
