<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\StatusInterface;
use Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:reservation:read",
 *         "creditGuaranty:reservationStatus:read",
 *         "creditGuaranty:borrower:read",
 *         "creditGuaranty:borrowerBusinessActivity:read",
 *         "creditGuaranty:project:read",
 *         "money:read",
 *         "nullableMoney:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:reservation:write",
 *         "creditGuaranty:reservation:write",
 *         "creditGuaranty:borrower:write",
 *         "creditGuaranty:borrowerBusinessActivity:write",
 *         "creditGuaranty:project:write",
 *         "money:write",
 *         "nullableMoney:write"
 *     }},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch",
 *         "delete"
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)"
 *         },
 *         "get"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_reservation")
 * @ORM\HasLifecycleCallbacks
 */
class Reservation implements TraceableStatusAwareInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private Program $program;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\Borrower", inversedBy="reservation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="id_borrower", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private Borrower $borrower;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\BorrowerBusinessActivity", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="id_borrower_business_activity")
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private ?BorrowerBusinessActivity $borrowerBusinessActivity = null;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\Project", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="id_project")
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private ?Project $project = null;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\ReservationStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @MaxDepth(1)
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private ?ReservationStatus $currentStatus;

    /**
     * @var Collection|ProgramStatus[]
     *
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ReservationStatus", mappedBy="reservation", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     *
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private Collection $statuses;

    /**
     * @var Collection|FinancingObject[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\FinancingObject", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $financingObjects;

    public function __construct(Program $program, Borrower $borrower, Staff $addedBy)
    {
        $this->program          = $program;
        $this->borrower         = $borrower;
        $this->financingObjects = new ArrayCollection();
        $this->added            = new DateTimeImmutable();
        $this->statuses         = new ArrayCollection();
        $this->setCurrentStatus(new ReservationStatus($this, ReservationStatus::STATUS_DRAFT, $addedBy));
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    public function setBorrower(Borrower $borrower): Reservation
    {
        $this->borrower = $borrower;

        return $this;
    }

    public function getBorrowerBusinessActivity(): ?BorrowerBusinessActivity
    {
        return $this->borrowerBusinessActivity;
    }

    public function setBorrowerBusinessActivity(?BorrowerBusinessActivity $borrowerBusinessActivity): Reservation
    {
        $this->borrowerBusinessActivity = $borrowerBusinessActivity;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): Reservation
    {
        $this->project = $project;

        return $this;
    }

    public function getFinancingObjects()
    {
        return $this->financingObjects;
    }

    /**
     * @Groups({"creditGuaranty:reservation:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:reservation:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * @return Collection|ReservationStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function getCurrentStatus(): StatusInterface
    {
        return $this->currentStatus;
    }

    /**
     * @param StatusInterface|ReservationStatus $status
     */
    public function setCurrentStatus(StatusInterface $status): Reservation
    {
        $this->currentStatus = $status;

        return $this;
    }
}
