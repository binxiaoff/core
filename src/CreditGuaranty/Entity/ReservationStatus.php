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
 *     normalizationContext={"groups": {"creditGuaranty:reservationStatus:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:reservationStatus:write"}},
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"}
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
 * @ORM\Table(name="credit_guaranty_reservation_status")
 *
 * @Assert\Callback(
 *     callback={"Unilend\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status", "allowedStatus": self::ALLOWED_STATUS }
 * )
 */
class ReservationStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    public const STATUS_DRAFT                         = 10;
    public const STATUS_SENT                          = 20;
    public const STATUS_CONFIRMED_BY_MANAGING_COMPANY = 30;
    public const STATUS_CONFIRMED_BY_FEI              = 35;
    public const STATUS_CONTRACT_FORMALIZED           = 40;

    public const ALLOWED_STATUS = [
        self::STATUS_DRAFT                         => [self::STATUS_SENT],
        self::STATUS_SENT                          => [self::STATUS_CONFIRMED_BY_MANAGING_COMPANY],
        self::STATUS_CONFIRMED_BY_MANAGING_COMPANY => [self::STATUS_CONFIRMED_BY_FEI],
        self::STATUS_CONFIRMED_BY_FEI              => [self::STATUS_CONTRACT_FORMALIZED],
        self::STATUS_CONTRACT_FORMALIZED           => [],
    ];

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_reservation", nullable=false, onDelete="CASCADE")
     */
    private Reservation $reservation;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     *
     * @Groups({"creditGuaranty:reservationStatus:read", "creditGuaranty:reservationStatus:write"})
     */
    private int $status;

    public function __construct(Reservation $reservation, int $status, Staff $addedBy)
    {
        $this->reservation = $reservation;
        $this->status      = $status;
        $this->addedBy     = $addedBy;
        $this->added       = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    public function getAttachedObject(): TraceableStatusAwareInterface
    {
        return $this->getReservation();
    }

    /**
     * @Groups({"creditGuaranty:reservationStatus:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }
}
