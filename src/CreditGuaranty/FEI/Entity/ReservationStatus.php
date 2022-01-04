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
use KLS\CreditGuaranty\FEI\Validator\Constraints\ReservationSent;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:reservationStatus:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:reservationStatus:write",
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
 * @ORM\Table(name="credit_guaranty_reservation_status")
 *
 * @Assert\Callback(
 *     callback={"KLS\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status", "allowedStatus": self::ALLOWED_STATUS }
 * )
 * @ReservationSent
 */
class ReservationStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    public const STATUS_DRAFT                              = 10;
    public const STATUS_SENT                               = 20;
    public const STATUS_WAITING_FOR_FEI                    = 30;
    public const STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION = 40;
    public const STATUS_ACCEPTED_BY_MANAGING_COMPANY       = 50;
    public const STATUS_CONTRACT_FORMALIZED                = 60;
    public const STATUS_ARCHIVED                           = -10;
    public const STATUS_REFUSED_BY_MANAGING_COMPANY        = -20;

    public const ALLOWED_STATUS = [
        self::STATUS_DRAFT => [self::STATUS_SENT],
        self::STATUS_SENT  => [
            self::STATUS_DRAFT,
            self::STATUS_WAITING_FOR_FEI,
            self::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
            self::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
            self::STATUS_ARCHIVED,
            self::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ],
        self::STATUS_WAITING_FOR_FEI => [
            self::STATUS_DRAFT,
            self::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
            self::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
            self::STATUS_ARCHIVED,
            self::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ],
        self::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION => [
            self::STATUS_DRAFT,
            self::STATUS_WAITING_FOR_FEI,
            self::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
            self::STATUS_ARCHIVED,
            self::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ],
        self::STATUS_ACCEPTED_BY_MANAGING_COMPANY => [
            self::STATUS_CONTRACT_FORMALIZED,
            self::STATUS_ARCHIVED,
        ],
        self::STATUS_CONTRACT_FORMALIZED         => [],
        self::STATUS_ARCHIVED                    => [],
        self::STATUS_REFUSED_BY_MANAGING_COMPANY => [],
    ];

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Reservation", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_reservation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"creditGuaranty:reservationStatus:write"})
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

    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     *
     * @Groups({"creditGuaranty:reservationStatus:read", "creditGuaranty:reservationStatus:write"})
     */
    private ?string $comment;

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): ReservationStatus
    {
        $this->comment = $comment;

        return $this;
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

    /**
     * @Assert\Callback
     */
    public function validateAvailability(ExecutionContextInterface $context): void
    {
        $lastStatus = $this->getAttachedObject()->getStatuses()->last();

        // We check the value only if it has previous status and only when we are adding a new status.
        if (false === $lastStatus instanceof self || $this->id || self::STATUS_SENT !== $this->status) {
            return;
        }

        $project = $this->getReservation()->getProject();

        if (false === $project->checkBalance()) {
            $context->buildViolation('CreditGuaranty.Reservation.project.fundingMoney.balanceExceeded')
                ->atPath('project.fundingMoney')
                ->addViolation()
            ;
        }

        if (false === $project->checkQuota()) {
            $context->buildViolation('CreditGuaranty.Reservation.project.fundingMoney.quotaExceeded')
                ->atPath('project.fundingMoney')
                ->addViolation()
            ;
        }

        if (false === $project->checkGradeAllocation()) {
            $context->buildViolation('CreditGuaranty.Reservation.project.fundingMoney.gradeAllocationExceeded')
                ->atPath('project.fundingMoney')
                ->addViolation()
            ;
        }

        if (false === $project->checkBorrowerTypeAllocation()) {
            $context->buildViolation('CreditGuaranty.Reservation.project.fundingMoney.borrowerTypeAllocationExceeded')
                ->atPath('project.fundingMoney')
                ->addViolation()
            ;
        }
    }
}
