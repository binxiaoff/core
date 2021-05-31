<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Entity\Interfaces\StatusInterface;
use Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableUserAddedTrait;
use Unilend\Core\Entity\Traits\CloneableTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Service\MoneyCalculator;
use Unilend\Core\Validator\Constraints\PreviousValue;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:program:read", "creditGuaranty:programStatus:read", "timestampable:read", "money:read", "nullableMoney:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:program:write", "money:write", "nullableMoney:write"}},
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "openapi_context": {
 *                 "parameters": {
 *                     {
 *                         "in": "query",
 *                         "name": "import",
 *                         "schema": {
 *                             "type": "string",
 *                             "minimum": 0,
 *                             "maximum": 1
 *                         },
 *                         "description": "Public id of the existing program from which you want to copy"
 *                     }
 *                 }
 *             }
 *         },
 *         "get"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"name"}, message="CreditGuaranty.Program.name.unique")
 */
class Program implements TraceableStatusAwareInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableUserAddedTrait;
    use CloneableTrait;

    public const COMPANY_GROUP_TAG_CORPORATE   = 'corporate';
    public const COMPANY_GROUP_TAG_AGRICULTURE = 'agriculture';

    /**
     * @ORM\Column(length=100, unique=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write", "creditGuaranty:participation:list"})
     */
    private string $name;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\CompanyGroupTag")
     * @ORM\JoinColumn(name="id_company_group_tag", nullable=false)
     *
     * @Assert\Expression(
     *     "this.isCompanyGroupTagValid()",
     *     message="CreditGuaranty.Program.companyGroupTag.invalid"
     * )
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private CompanyGroupTag $companyGroupTag;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.99")
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isInDraft()", message="CreditGuaranty.Program.cappedAt.draft"),
     *     @Assert\Sequentially({
     *
     *         @PreviousValue\ScalarNotAssignable(message="CreditGuaranty.Program.cappedAt.notChangeable"),
     *         @PreviousValue\ScalarNotResettable(message="CreditGuaranty.Program.cappedAt.notChangeable"),
     *         @PreviousValue\NumericGreaterThanOrEqual(message="CreditGuaranty.Program.cappedAt.greater")
     *     })
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $cappedAt;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isInDraft()", message="CreditGuaranty.Program.funds.draft"),
     *
     *     @PreviousValue\MoneyGreaterThanOrEqual(message="CreditGuaranty.Program.funds.greater")
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private Money $funds;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write", "creditGuaranty:participation:list"})
     */
    private ?DateTimeImmutable $distributionDeadline;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Count(max="10")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Length(max=200)
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?array $distributionProcess;

    /**
     * Duration in month.
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\GreaterThanOrEqual(1)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?int $guarantyDuration;

    /**
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $guarantyCoverage;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private NullableMoney $guarantyCost;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @MaxDepth(1)
     *
     * @Groups({"creditGuaranty:program:read"})
     */
    private ?ProgramStatus $currentStatus;

    /**
     * @var Collection|ProgramStatus[]
     *
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramStatus", mappedBy="program", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     *
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"creditGuaranty:program:read"})
     */
    private Collection $statuses;

    /**
     * @ORM\Column(length=60, nullable=true)
     *
     * @Assert\Choice(callback={CARatingType::class, "getConstList"})
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $ratingType = null;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * The max of a signed "smallint" is 32767
     * @Assert\Range(min="1", max="32767")
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?int $reservationDuration;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_managing_company", nullable=false)
     *
     * @ApiProperty(readableLink=false)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:create"})
     */
    private Company $managingCompany;

    // Subresource

    /**
     * @var Collection|ProgramGradeAllocation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="Unilend\CreditGuaranty\Entity\ProgramGradeAllocation",
     *     mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}, indexBy="grade"
     * )
     */
    private Collection $programGradeAllocations;

    /**
     * @var Collection|ProgramBorrowerTypeAllocation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation",
     *     mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}, indexBy="id_program_choice_option"
     * )
     */
    private Collection $programBorrowerTypeAllocations;

    /**
     * @var Collection|ProgramChoiceOption[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private Collection $programChoiceOptions;

    /**
     * @var Collection|ProgramContact[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramContact", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private Collection $programContacts;

    /**
     * @var Collection|ProgramEligibility[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibility", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private Collection $programEligibilities;

    /**
     * @var Collection|Participation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="Unilend\CreditGuaranty\Entity\Participation",
     *     mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}, indexBy="id_company"
     * )
     */
    private Collection $participations;

    /**
     * @var Collection|Reservation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="Unilend\CreditGuaranty\Entity\Reservation",
     *     mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}
     * )
     */
    private Collection $reservations;

    public function __construct(string $name, CompanyGroupTag $companyGroupTag, Money $funds, Staff $addedBy)
    {
        $this->name                           = $name;
        $this->companyGroupTag                = $companyGroupTag;
        $this->funds                          = $funds;
        $this->addedBy                        = $addedBy->getUser();
        $this->managingCompany                = $addedBy->getCompany();
        $this->guarantyCost                   = new NullableMoney();
        $this->statuses                       = new ArrayCollection();
        $this->programGradeAllocations        = new ArrayCollection();
        $this->programBorrowerTypeAllocations = new ArrayCollection();
        $this->programChoiceOptions           = new ArrayCollection();
        $this->participations                 = new ArrayCollection();
        $this->reservations                   = new ArrayCollection();
        $this->added                          = new DateTimeImmutable();
        $this->setCurrentStatus(new ProgramStatus($this, ProgramStatus::STATUS_DRAFT, $addedBy));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Program
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Program
    {
        $this->description = $description;

        return $this;
    }

    public function getCompanyGroupTag(): CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

    public function getCappedAt(): ?string
    {
        return $this->cappedAt;
    }

    public function setCappedAt(?string $cappedAt): Program
    {
        $this->cappedAt = $cappedAt;

        return $this;
    }

    public function getFunds(): Money
    {
        return $this->funds;
    }

    public function setFunds(Money $funds): Program
    {
        $this->funds = $funds;

        return $this;
    }

    public function getDistributionDeadline(): ?DateTimeImmutable
    {
        return $this->distributionDeadline;
    }

    public function setDistributionDeadline(?DateTimeImmutable $distributionDeadline): Program
    {
        $this->distributionDeadline = $distributionDeadline;

        return $this;
    }

    public function getDistributionProcess(): ?array
    {
        return $this->distributionProcess;
    }

    public function setDistributionProcess(?array $distributionProcess): Program
    {
        $this->distributionProcess = $distributionProcess;

        return $this;
    }

    public function getGuarantyDuration(): ?int
    {
        return $this->guarantyDuration;
    }

    public function setGuarantyDuration(?int $guarantyDuration): Program
    {
        $this->guarantyDuration = $guarantyDuration;

        return $this;
    }

    public function getGuarantyCoverage(): ?string
    {
        return $this->guarantyCoverage;
    }

    public function setGuarantyCoverage(?string $guarantyCoverage): Program
    {
        $this->guarantyCoverage = $guarantyCoverage;

        return $this;
    }

    public function getGuarantyCost(): NullableMoney
    {
        return $this->guarantyCost;
    }

    public function setGuarantyCost(NullableMoney $guarantyCost): Program
    {
        $this->guarantyCost = $guarantyCost;

        return $this;
    }

    /**
     * @return Collection|ProgramStatus[]
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
     * @param StatusInterface|ProgramStatus $status
     */
    public function setCurrentStatus(StatusInterface $status): Program
    {
        $this->currentStatus = $status;

        return $this;
    }

    public function getRatingType(): ?string
    {
        return $this->ratingType;
    }

    /**
     * @return $this
     */
    public function setRatingType(?string $ratingType): Program
    {
        if ($ratingType !== $this->ratingType) {
            $this->programGradeAllocations->clear();
        }

        $this->ratingType = $ratingType;

        return $this;
    }

    public function getReservationDuration(): ?int
    {
        return $this->reservationDuration;
    }

    public function setReservationDuration(?int $reservationDuration): Program
    {
        $this->reservationDuration = $reservationDuration;

        return $this;
    }

    public function getManagingCompany(): Company
    {
        return $this->managingCompany;
    }

    public function archive(Staff $archivedBy): Program
    {
        $this->setCurrentStatus(new ProgramStatus($this, ProgramStatus::STATUS_ARCHIVED, $archivedBy));

        return $this;
    }

    /**
     * Used in an expression constraints.
     */
    public function isCompanyGroupTagValid(): bool
    {
        return \in_array($this->getCompanyGroupTag()->getCode(), [self::COMPANY_GROUP_TAG_CORPORATE, self::COMPANY_GROUP_TAG_AGRICULTURE], true);
    }

    public function isInDraft(): bool
    {
        return ProgramStatus::STATUS_DRAFT === $this->getCurrentStatus()->getStatus();
    }

    public function isPaused(): bool
    {
        return ProgramStatus::STATUS_PAUSED === $this->getCurrentStatus()->getStatus();
    }

    public function isDistributed(): bool
    {
        return ProgramStatus::STATUS_DISTRIBUTED === $this->getCurrentStatus()->getStatus();
    }

    public function isArchived(): bool
    {
        return ProgramStatus::STATUS_ARCHIVED === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @param Collection|ProgramContact[] $programContacts
     */
    public function setProgramContacts(Collection $programContacts): Program
    {
        $this->programContacts = $programContacts;

        return $this;
    }

    /**
     * @return Collection|ProgramEligibility[]
     */
    public function getProgramEligibilities(): Collection
    {
        return $this->programEligibilities;
    }

    /**
     * @param Collection|ProgramEligibility[] $programEligibilities
     */
    public function setProgramEligibilities(Collection $programEligibilities): Program
    {
        $this->programEligibilities = $programEligibilities;

        return $this;
    }

    public function addProgramEligibility(ProgramEligibility $programEligibility): Program
    {
        $callback = fn (int $key, ProgramEligibility $existingProgramEligibility): bool => $existingProgramEligibility->getField() === $programEligibility->getField();
        if (
            $programEligibility->getProgram() === $this
            && false === $this->programEligibilities->exists($callback)
        ) {
            $this->programEligibilities->add($programEligibility);
        }

        return $this;
    }

    public function hasParticipant(Company $company): bool
    {
        return $this->getParticipants()->contains($company);
    }

    /**
     * @param Collection|Participation[] $participations
     */
    public function setParticipations(Collection $participations): Program
    {
        $this->participations = $participations;

        return $this;
    }

    /**
     * @return Collection|Participation[]
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    /**
     * @param Collection|ProgramGradeAllocation[] $programGradeAllocations
     */
    public function setProgramGradeAllocations(Collection $programGradeAllocations): Program
    {
        $this->programGradeAllocations = $programGradeAllocations;

        return $this;
    }

    /**
     * @return Collection|ProgramGradeAllocation[]
     */
    public function getProgramGradeAllocations(): Collection
    {
        return $this->programGradeAllocations;
    }

    /**
     * @return Collection|ProgramBorrowerTypeAllocation[]
     */
    public function getProgramBorrowerTypeAllocations(): Collection
    {
        return $this->programBorrowerTypeAllocations;
    }

    /**
     * @param Collection|ProgramBorrowerTypeAllocation[] $programBorrowerTypeAllocations
     */
    public function setProgramBorrowerTypeAllocations($programBorrowerTypeAllocations): Program
    {
        $this->programBorrowerTypeAllocations = $programBorrowerTypeAllocations;

        return $this;
    }

    public function removeProgramBorrowerTypeAllocation(ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation): Program
    {
        $this->programBorrowerTypeAllocations->removeElement($programBorrowerTypeAllocation);

        return $this;
    }

    public function addProgramBorrowerTypeAllocation(ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation): Program
    {
        $callback = static function (int $key, ProgramBorrowerTypeAllocation $existingProgramBorrowerTypeAllocation) use ($programBorrowerTypeAllocation): bool {
            return $existingProgramBorrowerTypeAllocation->getProgramChoiceOption() === $programBorrowerTypeAllocation->getProgramChoiceOption();
        };
        if (
            $programBorrowerTypeAllocation->getProgram() === $this
            && false === $this->programBorrowerTypeAllocations->exists($callback)
        ) {
            $this->programBorrowerTypeAllocations->add($programBorrowerTypeAllocation);
        }

        return $this;
    }

    /**
     * @return Collection|ProgramChoiceOption[]
     */
    public function getProgramChoiceOptions(): Collection
    {
        return $this->programChoiceOptions;
    }

    /**
     * @param Collection|ProgramChoiceOption[] $programChoiceOptions
     */
    public function setProgramChoiceOptions($programChoiceOptions): Program
    {
        $this->programChoiceOptions = $programChoiceOptions;

        return $this;
    }

    public function addProgramChoiceOption(ProgramChoiceOption $programChoiceOption): Program
    {
        $callback = function (int $key, ProgramChoiceOption $existingProgramChoiceOption) use ($programChoiceOption): bool {
            return $existingProgramChoiceOption->getField()       === $programChoiceOption->getField()
                && $existingProgramChoiceOption->getDescription() === $programChoiceOption->getDescription();
        };
        if (
            $programChoiceOption->getProgram() === $this
            && false === $this->programChoiceOptions->exists($callback)
        ) {
            $this->programChoiceOptions->add($programChoiceOption);
        }

        return $this;
    }

    /**
     * @param array $filter Possible criteria for the filter are
     *                      - grade: borrower's grade
     *                      - borrowerType: borrower type (ProgramChoiceOption) id
     *                      - exclude: an array of project ids to exclude
     */
    public function getTotalProjectFunds(array $filter = []): Money
    {
        $totalProjectFunds = new Money($this->funds->getCurrency());
        foreach ($this->reservations as $reservation) {
            if ($reservation->isSent()) {
                if (false === $this->applyTotalProjectFundsFilters($reservation, $filter)) {
                    continue;
                }

                if (false === $reservation->getProject() instanceof Project) {
                    throw new \RuntimeException(sprintf('Cannot find the project for reservation %d. Please check the date.', $reservation->getId()));
                }
                $totalProjectFunds = MoneyCalculator::add($reservation->getProject()->getFundingMoney(), $totalProjectFunds);
            }
        }

        return $totalProjectFunds;
    }

    /**
     * @Groups({"creditGuaranty:participation:list"})
     */
    public function getAmountAvailable(): MoneyInterface
    {
        return MoneyCalculator::subtract($this->getFunds(), $this->getTotalProjectFunds());
    }

    public function duplicate(Staff $duplicatedBy): Program
    {
        $duplicatedProgram          = clone $this;
        $duplicatedProgram->addedBy = $duplicatedBy->getUser();
        $duplicatedProgram->setCurrentStatus(new ProgramStatus($duplicatedProgram, ProgramStatus::STATUS_DRAFT, $duplicatedBy));

        return $duplicatedProgram;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations()
    {
        return $this->reservations;
    }

    protected function onClone(): Program
    {
        $this
            ->setProgramContacts($this->cloneCollection($this->programContacts))
            ->setProgramChoiceOptions($this->cloneCollection($this->programChoiceOptions))
            ->setProgramEligibilities($this->cloneCollection($this->programEligibilities))
            ->setProgramGradeAllocations($this->cloneCollection($this->programGradeAllocations))
            ->setProgramBorrowerTypeAllocations($this->cloneCollection($this->programBorrowerTypeAllocations))
            ->setParticipations($this->cloneCollection($this->participations))
        ;

        // Replace the ProgramChoiceOption (if not null) in the ProgramEligibilityConfiguration and ProgramBorrowerTypeAllocation (which are not remplace in the previous process)
        // by the newly created one. The new one was created with setProgramChoiceOptions() in the duplicated program.
        foreach ($this->getProgramEligibilities() as $programEligibility) {
            foreach ($programEligibility->getProgramEligibilityConfigurations() as $programEligibilityConfiguration) {
                $originalProgramChoiceOption = $programEligibilityConfiguration->getProgramChoiceOption();
                if ($originalProgramChoiceOption) {
                    $duplicatedProgramChoiceOption = $this->findProgramChoiceOption($originalProgramChoiceOption);
                    $programEligibilityConfiguration->setProgramChoiceOption($duplicatedProgramChoiceOption);
                }
            }
        }
        foreach ($this->getProgramBorrowerTypeAllocations() as $programBorrowerTypeAllocation) {
            $originalProgramChoiceOption = $programBorrowerTypeAllocation->getProgramChoiceOption();
            if ($originalProgramChoiceOption) {
                $duplicatedProgramChoiceOption = $this->findProgramChoiceOption($originalProgramChoiceOption);
                $programBorrowerTypeAllocation->setProgramChoiceOption($duplicatedProgramChoiceOption);
            }
        }

        return $this;
    }

    private function applyTotalProjectFundsFilters(Reservation $reservation, array $filter): bool
    {
        if (false === $reservation->getBorrower()->getBorrowerType() instanceof ProgramChoiceOption) {
            throw new \RuntimeException(sprintf('Cannot find the borrower type for reservation %d. Please check the date.', $reservation->getId()));
        }

        if (isset($filter['grade']) && $reservation->getBorrower()->getGrade() !== $filter['grade']) {
            return false;
        }

        if (isset($filter['borrowerType']) && $reservation->getBorrower()->getBorrowerType()->getId() !== $filter['borrowerType']) {
            return false;
        }

        if (isset($filter['exclude'])) {
            if (is_int($filter['exclude'])) {
                $filter['exclude'] = [$filter['exclude']];
            }
            if (in_array($reservation->getProgram()->getId(), $filter['exclude'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * clone the OneToMany relation.
     */
    private function cloneCollection(Collection $collectionToDuplicate): ArrayCollection
    {
        $clonedArrayCollection = new ArrayCollection();
        foreach ($collectionToDuplicate as $item) {
            $clonedItem = clone $item;
            if (false === method_exists($clonedItem, 'setProgram')) {
                throw new \LogicException(sprintf('Cannot find the method setProgram of the class %s. Make sure it exists or it is accessible.', get_class($clonedItem)));
            }
            $clonedItem->setProgram($this);
            $clonedArrayCollection->add($clonedItem);
        }

        return $clonedArrayCollection;
    }

    /**
     * Find the choice option in the duplicated program which has the same attribut as the original one.
     */
    private function findProgramChoiceOption(ProgramChoiceOption $originalProgramChoiceOption): ProgramChoiceOption
    {
        $clonedProgramChoiceOption = $this->getProgramChoiceOptions()
            ->filter(
                fn (ProgramChoiceOption $item) => $item->getField() === $originalProgramChoiceOption->getField()
                    && $item->getDescription() === $originalProgramChoiceOption->getDescription()
            )
            ->first()
        ;
        if (false === $clonedProgramChoiceOption instanceof ProgramChoiceOption) {
            throw new \LogicException(sprintf(
                'The new program choice option cannot be found on program %s with field %s and description %s',
                $this->getName(),
                $originalProgramChoiceOption->getField()->getId(),
                $originalProgramChoiceOption->getDescription()
            ));
        }

        return $clonedProgramChoiceOption;
    }

    /**
     * @return Collection|Company[]
     */
    private function getParticipants(): Collection
    {
        return $this->participations->map(function (Participation $participation) {
            return $participation->getParticipant();
        });
    }
}
