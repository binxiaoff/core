<?php

declare(strict_types=1);

namespace KLS\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
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
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\Core\Service\MoneyCalculator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * "money:read" is needed for allocation.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participation:read",
 *             "money:read",
 *             "nullableMoney:read",
 *             "lendingRate:read"
 *         }
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:participation:create",
 *                     "agency:participation:write",
 *                     "nullableMoney:write",
 *                     "money:write",
 *                     "agency:participationTrancheAllocation:write"
 *                 }
 *             },
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "validation_groups": {Participation::class, "getCurrentValidationGroups"}
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:participation:write",
 *                     "agency:projectPartaker:write",
 *                     "nullableMoney:write",
 *                     "money:write",
 *                     "agency:participationTrancheAllocation:write"
 *                 }
 *             },
 *             "security": "is_granted('edit', object)",
 *             "validation_groups": {Participation::class, "getCurrentValidationGroups"}
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         },
 *         "get_dataroom": {
 *             "method": "GET",
 *             "security": "is_granted('view', object)",
 *             "path": "/agency/participations/{publicId}/dataroom/{path?}",
 *             "controller": Get::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *         },
 *         "post_dataroom": {
 *             "method": "POST",
 *             "deserialize": false,
 *             "security": "is_granted('dataroom', object)",
 *             "path": "/agency/participations/{publicId}/dataroom/{path?}",
 *             "controller": Post::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *         },
 *         "delete_dataroom": {
 *             "method": "DELETE",
 *             "security": "is_granted('dataroom', object)",
 *             "path": "/agency/participations/{publicId}/dataroom/{path?}",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *         },
 *     }
 * )
 * @ORM\Entity
 * @ORM\Table(name="agency_participation", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_participation_pool", "id_participant"})
 * })
 *
 * @UniqueEntity(fields={"pool", "participant"})
 *
 * TODO The .publicId might be dropped in favor of using iri for filter when https://github.com/api-platform/core/issues/3575 is solved
 * @ApiFilter(
 *     filterClass=SearchFilter::class,
 *     properties={"participant.publicId": "exact", "pool.project.publicId": "exact"}
 * )
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "company:read",
 *             "agency:participationTrancheAllocation:read",
 *             "agency:participationMember:read",
 *             "user:read",
 *             "agency:tranche:read"
 *         }
 *     }
 * )
 */
class Participation extends AbstractProjectPartaker
{
    /**
     * @var Collection|ParticipationMember[]
     *
     * @ORM\OneToMany(targetEntity=ParticipationMember::class, mappedBy="participation", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getParticipation() === this")
     * })
     *
     * @Groups({"agency:participation:read"})
     */
    protected Collection $members;

    /**
     * @ORM\ManyToOne(targetEntity=ParticipationPool::class, inversedBy="participations")
     * @ORM\JoinColumn(name="id_participation_pool", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:participation:read", "agency:participation:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private ParticipationPool $pool;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="id_participant", nullable=false)
     *
     * @Groups({"agency:participation:read", "agency:participation:create"})
     *
     * @Assert\NotBlank
     */
    private Company $participant;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $participantCommission;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private NullableMoney $agentCommission;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private NullableMoney $arrangerCommission;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private NullableMoney $deputyArrangerCommission;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private bool $prorata;

    /**
     * @var ParticipationTrancheAllocation[]|Collection
     *
     * @ORM\OneToMany(targetEntity=ParticipationTrancheAllocation::class, cascade={"persist", "remove"}, mappedBy="participation", orphanRemoval=true)
     *
     * @Assert\Count(min="1", groups={"published"})
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getParticipation() === this")
     * })
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private Collection $allocations;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_confidential_drive", nullable=false, unique=true)
     */
    private Drive $confidentialDrive;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"agency:participation:read"})
     */
    private ?DateTimeImmutable $archivingDate;

    public function __construct(
        ParticipationPool $pool,
        Company $participant,
        ?NullableMoney $capital = null
    ) {
        parent::__construct($participant->getSiren() ?? '', $capital ?? new NullableMoney($pool->getProject()->getCurrency(), '0'));
        $this->pool                     = $pool;
        $this->participant              = $participant;
        $this->prorata                  = false;
        $this->participantCommission    = '0';
        $this->arrangerCommission       = new NullableMoney();
        $this->agentCommission          = new NullableMoney();
        $this->deputyArrangerCommission = new NullableMoney();
        $this->allocations              = new ArrayCollection();
        $this->archivingDate            = null;
        $this->members                  = new ArrayCollection();
        $this->confidentialDrive        = new Drive();
    }

    public function getParticipant(): Company
    {
        return $this->participant;
    }

    public function getPool(): ParticipationPool
    {
        return $this->pool;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getProject(): Project
    {
        return $this->pool->getProject();
    }

    public function isAgent(): bool
    {
        return $this->getProject()->getAgent()->getCompany() === $this->getParticipant();
    }

    public function getAgentCommission(): NullableMoney
    {
        return $this->agentCommission;
    }

    public function setAgentCommission(NullableMoney $agentCommission): Participation
    {
        $this->agentCommission = $agentCommission;

        return $this;
    }

    public function isArranger(): bool
    {
        return false === $this->getArrangerCommission()->isNull();
    }

    public function getArrangerCommission(): NullableMoney
    {
        return $this->arrangerCommission;
    }

    public function setArrangerCommission(NullableMoney $arrangerCommission): Participation
    {
        $this->arrangerCommission = $arrangerCommission;

        return $this;
    }

    public function isDeputyArranger(): bool
    {
        return false === $this->getDeputyArrangerCommission()->isNull();
    }

    public function getDeputyArrangerCommission(): NullableMoney
    {
        return $this->deputyArrangerCommission;
    }

    public function setDeputyArrangerCommission(NullableMoney $deputyArrangerCommission): Participation
    {
        $this->deputyArrangerCommission = $deputyArrangerCommission;

        return $this;
    }

    /**
     * @return iterable|ParticipationTrancheAllocation[]
     */
    public function getAllocations()
    {
        return $this->allocations;
    }

    /**
     * @param iterable|ParticipationTrancheAllocation[] $allocations
     */
    public function setAllocations(iterable $allocations): Participation
    {
        $this->allocations = $allocations;

        return $this;
    }

    public function isSecondary(): bool
    {
        return $this->getPool()->isSecondary();
    }

    public function isPrimary(): bool
    {
        return $this->getPool()->isPrimary();
    }

    public function getParticipantCommission(): ?string
    {
        return $this->participantCommission;
    }

    public function setParticipantCommission(?string $participantCommission): Participation
    {
        $this->participantCommission = $participantCommission;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getFinalAllocation(): MoneyInterface
    {
        $result = MoneyCalculator::sum($this->allocations->map(fn (ParticipationTrancheAllocation $allocation) => $allocation->getAllocation())->toArray());

        if (null === $result->getCurrency()) {
            $result = new NullableMoney($this->getProject()->getCurrency(), $result->getAmount() ?? '0');
        }

        return $result;
    }

    public function isProrata(): bool
    {
        return $this->prorata;
    }

    public function setProrata(bool $prorata): Participation
    {
        $this->prorata = $prorata;

        return $this;
    }

    public function getConfidentialDrive(): Drive
    {
        return $this->confidentialDrive;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getBankInstitution(): ?string
    {
        return $this->bankInstitution;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setBankInstitution(?string $bankInstitution): AbstractProjectPartaker
    {
        $this->bankInstitution = $bankInstitution;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getBankAddress(): ?string
    {
        return $this->bankAddress;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setBankAddress(?string $bankAddress): AbstractProjectPartaker
    {
        $this->bankAddress = $bankAddress;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setBic(?string $bic): AbstractProjectPartaker
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setIban(?string $iban): AbstractProjectPartaker
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setMatriculationNumber(string $matriculationNumber): AbstractProjectPartaker
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getCapital(): NullableMoney
    {
        return parent::getCapital();
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setCapital(NullableMoney $capital): AbstractProjectPartaker
    {
        return parent::setCapital($capital);
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setRcs(?string $rcs): AbstractProjectPartaker
    {
        $this->rcs = $rcs;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getCorporateName(): ?string
    {
        return $this->corporateName;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setCorporateName(?string $corporateName): AbstractProjectPartaker
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setHeadOffice(?string $headOffice): AbstractProjectPartaker
    {
        $this->headOffice = $headOffice;

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setLegalForm(?string $legalForm): AbstractProjectPartaker
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups.
     *
     * @param Participation $participation
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(self $participation): array
    {
        $validationGroups = ['Default', 'Project'];

        if ($participation->getProject()->isPublished()) {
            $validationGroups[] = 'published';
        }

        return $validationGroups;
    }

    public function addAllocation(ParticipationTrancheAllocation $participationTrancheAllocation): Participation
    {
        if (
            false === $this->allocations->exists(
                fn ($key, ParticipationTrancheAllocation $item) => $item->getTranche() === $participationTrancheAllocation->getTranche()
            )
        ) {
            $this->allocations->add($participationTrancheAllocation);
            $participationTrancheAllocation->getTranche()->addAllocation($participationTrancheAllocation);
        }

        return $this;
    }

    public function removeAllocation(ParticipationTrancheAllocation $participationTrancheAllocation): Participation
    {
        $this->allocations->removeElement($participationTrancheAllocation);

        return $this;
    }

    public function archive(): Participation
    {
        if (false === $this->isArchived()) {
            $this->archivingDate = new DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getPoolShare(): float
    {
        $allocationSum = $this->getPool()->getAllocationSum();

        if ((float) 0 === (float) ($allocationSum->getAmount())) {
            return 0;
        }

        return MoneyCalculator::ratio($this->getFinalAllocation(), $allocationSum);
    }

    /**
     * @Groups({"agency:participation:read"})
     */
    public function getActivePoolShare(): float
    {
        if ($this->isArchived()) {
            return 0.0;
        }

        $allocationSum = $this->getPool()->getActiveParticipantAllocationSum();

        if ((float) 0 === (float) ($allocationSum->getAmount())) {
            return 0.0;
        }

        return MoneyCalculator::ratio($this->getFinalAllocation(), $allocationSum);
    }

    /**
     * @Assert\Callback
     */
    public function validateParticipant(ExecutionContextInterface $context)
    {
        foreach ($this->getProject()->getParticipations() as $participation) {
            if (
                $participation !== $this
                && $participation->getParticipant()->getSiren() === $this->getParticipant()->getSiren()
            ) {
                $context->buildViolation('Agency.Participation.participant.duplicate')
                    ->setParameter('participant', $participation->getParticipant()->getDisplayName())
                    ->setParameter('pool', $participation->getPool()->isSecondary() ? 'secondary' : 'primary')
                    ->atPath('participant')
                    ->addViolation()
                ;
            }
        }
    }

    public function validateAgentCommission(ExecutionContextInterface $context)
    {
        if ($this->isAgent() && $this->getAgentCommission()->isNull()) {
            $context->buildViolation('Agency.Participation.agentCommission.missingCommission')
                ->addViolation()
            ;
        }

        if (false === $this->isAgent() && false === $this->getAgentCommission()->isNull()) {
            $context->buildViolation('Agency.Participation.agentCommission.notAgent')
                ->addViolation()
            ;
        }
    }
}
