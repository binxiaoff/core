<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Interfaces\DriveCarrierInterface;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Service\MoneyCalculator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:reservation:read",
 *             "creditGuaranty:reservationStatus:read",
 *             "creditGuaranty:borrower:read",
 *             "creditGuaranty:project:read",
 *             "money:read",
 *             "nullableMoney:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:reservation:write",
 *             "money:write",
 *             "nullableMoney:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *         "get_reservation_dataroom": {
 *             "method": "GET",
 *             "path": "/credit_guaranty/reservation/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('view', object)",
 *             "controller": Get::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "read",
 *             },
 *         },
 *         "post_reservation_dataroom": {
 *             "method": "POST",
 *             "path": "/credit_guaranty/reservation/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "deserialize": false,
 *             "controller": Post::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "read",
 *             },
 *         },
 *         "delete_reservation_dataroom": {
 *             "method": "DELETE",
 *             "path": "/credit_guaranty/reservation/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *         "get",
 *         "api_credit_guaranty_programs_reservations_get_subresource": {
 *             "method": "GET",
 *             "pagination_client_items_per_page": true,
 *         },
 *     },
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 * @ApiFilter(OrderFilter::class, properties={"currentStatus.added"}, arguments={"orderParameterName": "order"})
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_reservation")
 * @ORM\HasLifecycleCallbacks
 */
class Reservation implements TraceableStatusAwareInterface, DriveCarrierInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="reservations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private Program $program;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private ?string $name;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_managing_company", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:create"})
     */
    private Company $managingCompany;

    /**
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Borrower", inversedBy="reservation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="id_borrower", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private Borrower $borrower;

    /**
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Project", inversedBy="reservation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private Project $project;

    /**
     * @var Collection|FinancingObject[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="KLS\CreditGuaranty\FEI\Entity\FinancingObject", mappedBy="reservation", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $financingObjects;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\Drive", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_drive", nullable=false, unique=true)
     */
    private Drive $drive;

    /**
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReservationStatus", cascade={"persist"})
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
     * @var Collection|ReservationStatus[]
     *
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReservationStatus", mappedBy="reservation", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     *
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private Collection $statuses;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:write"})
     */
    private ?DateTimeImmutable $signingDate = null;

    public function __construct(Program $program, Staff $addedBy)
    {
        $this->program          = $program;
        $this->managingCompany  = $addedBy->getCompany();
        $this->borrower         = new Borrower($this);
        $this->project          = new Project($this);
        $this->financingObjects = new ArrayCollection();
        $this->drive            = new Drive();
        $this->added            = new DateTimeImmutable();
        $this->statuses         = new ArrayCollection();
        $this->setCurrentStatus(new ReservationStatus($this, ReservationStatus::STATUS_DRAFT, $addedBy));
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @Groups({"creditGuaranty:reservation:read"})
     */
    public function getProgramName(): string
    {
        return $this->program->getName();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Reservation
    {
        $this->name = $name;

        return $this;
    }

    public function getManagingCompany(): Company
    {
        return $this->managingCompany;
    }

    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getFinancingObjects()
    {
        return $this->financingObjects;
    }

    public function addFinancingObject(FinancingObject $financingObject): Reservation
    {
        if (false === $this->financingObjects->contains($financingObject)) {
            $this->financingObjects->add($financingObject);
        }

        return $this;
    }

    public function getDrive(): Drive
    {
        return $this->drive;
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

    public function archive(Staff $archivedBy): Reservation
    {
        $this->setCurrentStatus(new ReservationStatus($this, ReservationStatus::STATUS_ARCHIVED, $archivedBy));

        return $this;
    }

    public function isInDraft(): bool
    {
        return ReservationStatus::STATUS_DRAFT === $this->getCurrentStatus()->getStatus();
    }

    public function isSent(): bool
    {
        return ReservationStatus::STATUS_SENT <= $this->getCurrentStatus()->getStatus();
    }

    public function isAcceptedByManagingCompany(): bool
    {
        return ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY === $this->getCurrentStatus()->getStatus();
    }

    public function isRefusedByManagingCompany(): bool
    {
        return ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY === $this->getCurrentStatus()->getStatus();
    }

    public function isFormalized(): bool
    {
        return ReservationStatus::STATUS_CONTRACT_FORMALIZED === $this->getCurrentStatus()->getStatus();
    }

    public function isArchived(): bool
    {
        return ReservationStatus::STATUS_ARCHIVED === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return Collection|ReservationStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function getDateByStatus(int $status): ?DateTimeImmutable
    {
        foreach ($this->getStatuses() as $reservationStatus) {
            if ($status === $reservationStatus->getStatus()) {
                return $reservationStatus->getAdded();
            }
        }

        return null;
    }

    /**
     * @Groups({"creditGuaranty:reservation:read"})
     */
    public function getSentDate(): ?DateTimeImmutable
    {
        return $this->getDateByStatus(ReservationStatus::STATUS_SENT);
    }

    /**
     * @Groups({"creditGuaranty:reservation:read"})
     */
    public function getAcceptedByManagingCompanyDate(): ?DateTimeImmutable
    {
        return $this->getDateByStatus(ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY);
    }

    public function getSigningDate(): ?DateTimeImmutable
    {
        return $this->signingDate;
    }

    public function setSigningDate(?DateTimeImmutable $signingDate): Reservation
    {
        $this->signingDate = $signingDate;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:reservation:read"})
     */
    public function isGrossSubsidyEquivalentEligible(): bool
    {
        $project = $this->getProject();

        if (false === ($project instanceof Project)) {
            return false;
        }

        $financingObjects = $this->getFinancingObjects();

        if ($financingObjects->count() < 1) {
            return false;
        }

        $grossSubsidyEquivalents = $financingObjects->map(static fn (FinancingObject $financingObject) => $financingObject->getGrossSubsidyEquivalent())->toArray();
        $esbTotal                = MoneyCalculator::sum($grossSubsidyEquivalents);
        $maxFeiCredit            = $project->getMaxFeiCredit();

        $comparison = MoneyCalculator::compare($esbTotal, $maxFeiCredit);

        // $esbTotal should be superior or equal to $maxFeiCredit
        return 0 >= $comparison;
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
}
