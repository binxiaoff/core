<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\CreditGuaranty\FEI\Validator\Constraints\ProgramDistributed;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:programStatus:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:programStatus:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"},
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 * )
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program_status")
 *
 * @Assert\Callback(
 *     callback={"KLS\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status", "allowedStatus": self::ALLOWED_STATUS }
 * )
 * @ProgramDistributed
 */
class ProgramStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    public const STATUS_ARCHIVED    = -10;
    public const STATUS_DRAFT       = 10;
    public const STATUS_DISTRIBUTED = 20;
    public const STATUS_PAUSED      = 30;

    public const ALLOWED_STATUS = [
        self::STATUS_ARCHIVED    => [],
        self::STATUS_DRAFT       => [self::STATUS_DISTRIBUTED],
        self::STATUS_DISTRIBUTED => [self::STATUS_ARCHIVED, self::STATUS_PAUSED],
        self::STATUS_PAUSED      => [self::STATUS_ARCHIVED, self::STATUS_DISTRIBUTED],
    ];

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_program", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"creditGuaranty:programStatus:read", "creditGuaranty:programStatus:write"})
     */
    private Program $program;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     *
     * @Groups({"creditGuaranty:programStatus:read", "creditGuaranty:programStatus:write"})
     */
    private int $status;

    public function __construct(Program $program, int $status, Staff $addedBy)
    {
        $this->program = $program;
        $this->status  = $status;
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

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
