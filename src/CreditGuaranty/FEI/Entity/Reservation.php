<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\Interfaces\DriveCarrierInterface;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Service\MoneyCalculator;
use KLS\CreditGuaranty\FEI\Controller\ProgramEligibilityConditions;
use KLS\CreditGuaranty\FEI\Controller\Reservation\Ineligibilities;
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
 *                 "openapi_definition_name": "item-get_reservation_dataroom-read",
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
 *                 "openapi_definition_name": "item-post_reservation_dataroom-read",
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
 *         "get_ineligibilities": {
 *             "method": "GET",
 *             "path": "credit_guaranty/reservations/{publicId}/ineligibilities",
 *             "controller": Ineligibilities::class,
 *             "security": "is_granted('check_eligibility', object)",
 *         },
 *         "get_program_eligibility_conditions": {
 *             "method": "GET",
 *             "path": "credit_guaranty/reservations/{publicId}/program_eligibility_conditions",
 *             "controller": ProgramEligibilityConditions::class,
 *             "security": "is_granted('check_eligibility', object)",
 *             "normalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:programEligibilityCondition:read",
 *                     "creditGuaranty:programEligibilityCondition:field",
 *                     "timestampable:read",
 *                 },
 *                 "openapi_definition_name": "item-get_program_eligibility_conditions-read",
 *             },
 *             "openapi_context": {
 *                 "parameters": {
 *                     {
 *                         "in": "query",
 *                         "name": "eligible",
 *                         "schema": {
 *                             "type": "boolean",
 *                             "enum": {0, 1, false, true},
 *                         },
 *                         "required": false,
 *                     },
 *                 },
 *                 "responses": {
 *                     "200": {
 *                         "content": {
 *                             "application/json+ld": {},
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:reservation:create"
 *                 },
 *                 "openapi_definition_name": "item-post-create",
 *             },
 *         },
 *         "get",
 *     },
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"name": "partial", "program.publicId": "exact"})
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 * @ApiFilter(OrderFilter::class, properties={"added"})
 * @ApiFilter("KLS\CreditGuaranty\FEI\Filter\ReservationSentDateOrderFilter")
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
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:create"})
     */
    private Program $program;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({
     *     "creditGuaranty:reservation:read",
     *     "creditGuaranty:reservation:create",
     *     "creditGuaranty:reservation:update:draft"
     * })
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_managing_company", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:create"})
     */
    private Company $managingCompany;

    /**
     * @ORM\OneToOne(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\Borrower",
     *     inversedBy="reservation",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="id_borrower", nullable=false)
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private Borrower $borrower;

    /**
     * @ORM\OneToOne(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\Project",
     *     inversedBy="reservation",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
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
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\FinancingObject",
     *     mappedBy="reservation",
     *     cascade={"remove"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @Groups({"creditGuaranty:reservation:read"})
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
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ReservationStatus",
     *     mappedBy="reservation",
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     *
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"creditGuaranty:reservation:read"})
     */
    private Collection $statuses;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Groups({"creditGuaranty:reservation:read", "creditGuaranty:reservation:update:accepted"})
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

    public function getRefusedByManagingCompanyDate(): ?DateTimeImmutable
    {
        return $this->getDateByStatus(ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY);
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
        $financingObjects = $this->getFinancingObjects();

        if (0 === $financingObjects->count()) {
            return false;
        }

        $project      = $this->getProject();
        $esbTotal     = $project->getTotalGrossSubsidyEquivalent();
        $maxFeiCredit = $project->getMaxFeiCredit();

        if (null === $esbTotal->getAmount() || null === $maxFeiCredit->getAmount()) {
            return false;
        }

        $comparison = MoneyCalculator::compare($esbTotal, $maxFeiCredit);

        // $esbTotal should be inferior or equal to $maxFeiCredit
        return $comparison <= 0;
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
