<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Controller\Dataroom\Delete;
use Unilend\Core\Controller\Dataroom\Get;
use Unilend\Core\Controller\Dataroom\Post;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Model\Bitmask;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participation:read",
 *             "nullableMoney:read"
 *         }
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:participation:create",
 *                     "agency:participation:write",
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
 *                     "money:write",
 *                     "agency:participationTrancheAllocation:write"
 *                 }
 *             },
 *             "security_post_denormalize": "is_granted('edit', object)",
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
 *             "security": "is_granted('view', object)",
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
 *             "security": "is_granted('view', object)",
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
 */
class Participation extends AbstractProjectPartaker
{
    public const RESPONSIBILITY_AGENT           = 1 << 0;
    public const RESPONSIBILITY_ARRANGER        = 1 << 1;
    public const RESPONSIBILITY_DEPUTY_ARRANGER = 1 << 2;

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
     * @Groups({"agency:participation:read"})
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
     * @ORM\Column(type="bitmask", nullable=false)
     *
     * @Assert\Expression(expression="false === (0 === value && this.isSecondary())", message="Agency.Participation.responsabilities.secondary")
     * @Assert\Expression(expression="false === (this.isArranger() && this.isDeputyArranger())", message="Agency.Participation.responsabilities.arranger")
     * @Assert\Expression(
     *     expression="(this.isAgent() && (this.getParticipant() === this.getProject().getAgentCompany())) || (false === this.isAgent())",
     *     message="Agency.Participation.responsabilities.agent"
     * )
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private Bitmask $responsibilities;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(expression="null === value || false === this.isAgent()", message="Agency.Participation.commission.agent")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $agentCommission;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Assert\Expression(expression="null === value || false === this.isArranger()", message="Agency.Participation.commission.arranger")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $arrangerCommission;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Assert\Expression(expression="null === value || false === this.isDeputyArranger()", message="Agency.Participation.commission.deputyArranger")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $deputyArrangerCommission;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     *
     * @Assert\Valid
     */
    private Money $finalAllocation;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private bool $prorata;

    /**
     * @var ParticipationTrancheAllocation[]|Collection
     *
     * @ORM\OneToMany(targetEntity=ParticipationTrancheAllocation::class, cascade={"persist", "remove"}, mappedBy="participation")
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
     */
    private ?DateTimeImmutable $archivingDate;

    public function __construct(
        ParticipationPool $project,
        Company $participant,
        Money $finalAllocation,
        Money $capital
    ) {
        parent::__construct($participant->getSiren() ?? '', $capital);
        $this->responsibilities         = new Bitmask(0);
        $this->pool                     = $project;
        $this->finalAllocation          = $finalAllocation;
        $this->participant              = $participant;
        $this->prorata                  = false;
        $this->participantCommission    = '0';
        $this->arrangerCommission       = null;
        $this->agentCommission          = null;
        $this->deputyArrangerCommission = null;
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

    public function getProject(): Project
    {
        return $this->pool->getProject();
    }

    public function getResponsibilities(): Bitmask
    {
        return $this->responsibilities;
    }

    public function setResponsibilities(Bitmask $responsibilities): Participation
    {
        $this->responsibilities = $responsibilities;

        return $this;
    }

    public function isAgent(): bool
    {
        // TODO deduce from data instead if possible
        return $this->responsibilities->has(static::RESPONSIBILITY_AGENT);
    }

    public function getAgentCommission(): ?string
    {
        return $this->agentCommission;
    }

    public function setAgentCommission(?string $agentCommission): Participation
    {
        $this->agentCommission = $agentCommission;

        return $this;
    }

    public function isArranger(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_ARRANGER);
    }

    public function getArrangerCommission(): ?string
    {
        return $this->arrangerCommission;
    }

    public function setArrangerCommission(?string $arrangerCommission): Participation
    {
        $this->arrangerCommission = $arrangerCommission;

        return $this;
    }

    public function isDeputyArranger(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_DEPUTY_ARRANGER);
    }

    public function getDeputyArrangerCommission(): ?string
    {
        return $this->deputyArrangerCommission;
    }

    public function setDeputyArrangerCommission(?string $deputyArrangerCommission): Participation
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
     *
     * @return Participation
     */
    public function setAllocations(iterable $allocations)
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

    public function getFinalAllocation(): Money
    {
        return $this->finalAllocation;
    }

    public function setFinalAllocation(Money $finalAllocation): Participation
    {
        $this->finalAllocation = $finalAllocation;

        return $this;
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
    public function getCapital(): Money
    {
        return $this->capital;
    }

    /**
     * @Groups({"agency:participation:write"})
     */
    public function setCapital(Money $capital): AbstractProjectPartaker
    {
        $this->capital = $capital;

        return $this;
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
            $validationGroups[] = ['published'];
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

    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }
}
