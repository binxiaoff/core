<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Generator;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Constant\CAInternalRating;
use KLS\Core\Entity\Constant\FundingSpecificity;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Embeddable\NullablePerson;
use KLS\Core\Entity\Interfaces\DriveCarrierInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\Syndication\Agency\Controller\Project\GetCovenants;
use KLS\Syndication\Agency\Controller\Project\GetTerms;
use KLS\Syndication\Agency\Entity\Versioned\VersionedProject;
use KLS\Syndication\Agency\Filter\ApiPlatform\ProjectFilter;
use KLS\Syndication\Arrangement\Entity\Project as ArrangementProject;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * TODO CALS-4266 "agency:term:read" should not used (too much data returned without filter).
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:project:read",
 *             "timestampable:read",
 *             "money:read",
 *             "nullablePerson:read",
 *             "nullableMoney:read",
 *             "lendingRate:read"
 *         },
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "validation_groups": {Project::class, "getCurrentValidationGroups"},
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:project:write",
 *                     "money:write",
 *                     "nullablePerson:write",
 *                     "nullableMoney:write"
 *                 }
 *             },
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
 *                         "description": "Public id of the imported arragement project"
 *                     }
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "terms": {
 *             "path": "/agency/projects/{publicId}/terms",
 *             "method": "GET",
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {
 *                 "groups": {"agency:term:read"},
 *             },
 *             "controller": GetTerms::class
 *         },
 *         "covenants": {
 *             "path": "/agency/projects/{publicId}/covenants",
 *             "method": "GET",
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {
 *                 "groups": {"agency:covenant:read", "agency:inequality:read"},
 *             },
 *             "controller": GetCovenants::class
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:project:write",
 *                     "money:write",
 *                     "nullablePerson:write",
 *                     "nullableMoney:write",
 *                     "agency:covenant:update"
 *                 }
 *             },
 *             "validation_groups": {Project::class, "getCurrentValidationGroups"}
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *         "get_borrower_dataroom_shared": {
 *             "method": "GET",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/shared/{path?}",
 *             "security": "is_granted('agent', object) || is_granted('borrower', object)",
 *             "controller": Get::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "borrowerSharedDrive"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             }
 *         },
 *         "post_borrower_dataroom_shared": {
 *             "method": "POST",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/shared/{path?}",
 *             "security": "is_granted('agent', object) || is_granted('borrower', object)",
 *             "deserialize": false,
 *             "controller": Post::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "borrowerSharedDrive"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             }
 *         },
 *         "delete_borrower_dataroom_shared": {
 *             "method": "DELETE",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/shared/{path?}",
 *             "security": "is_granted('agent', object)",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "borrowerSharedDrive"
 *             }
 *         },
 *         "get_borrower_dataroom_confidential": {
 *             "method": "GET",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/confidential/{path?}",
 *             "security": "is_granted('borrower', object)",
 *             "controller": Get::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "borrowerConfidentialDrive"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             }
 *         },
 *         "post_borrower_dataroom_confidential": {
 *             "method": "POST",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/confidential/{path?}",
 *             "security": "is_granted('borrower', object)",
 *             "deserialize": false,
 *             "controller": Post::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "borrowerConfidentialDrive"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             }
 *         },
 *         "delete_borrower_dataroom_confidential": {
 *             "method": "DELETE",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/confidential/{path?}",
 *             "security": "is_granted('borrower', object)",
 *             "deserialize": false,
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "borrowerConfidentialDrive"
 *             }
 *         }
 *     }
 * )
 *
 * @ORM\Table(name="agency_project")
 * @ORM\Entity
 *
 * @Gedmo\Loggable(logEntryClass=VersionedProject::class)
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "company:read",
 *             "companyGroupTag:read",
 *             "agency:agent:read",
 *             "agency:agentMember:read",
 *             "agency:borrower:read",
 *             "agency:borrowerMember:read",
 *             "agency:borrowerTrancheShare:read",
 *             "agency:participationPool:read",
 *             "agency:participation:read",
 *             "agency:participationPool:read",
 *             "agency:participationMember:read",
 *             "agency:participationTrancheAllocation:read",
 *             "agency:tranche:read",
 *             "agency:covenant:read",
 *             "agency:covenantRule:read",
 *             "agency:term:read",
 *             "file:read",
 *             "fileVersion:read",
 *             "user:read",
 *         }
 *     }
 * )
 *
 * @ApiFilter(filterClass=NumericFilter::class, properties={"currentStatus"})
 * @ApiFilter(filterClass=ProjectFilter::class, arguments={})
 */
class Project implements DriveCarrierInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use ConstantsAwareTrait;

    public const STATUS_DRAFT     = 10;
    public const STATUS_PUBLISHED = 20;
    public const STATUS_ARCHIVED  = -10;
    public const STATUS_FINISHED  = -20;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Syndication\Agency\Entity\Agent", mappedBy="project", cascade={"persist", "remove"})
     *
     * @Assert\Valid
     *
     * @Groups({"agency:project:read"})
     */
    private Agent $agent;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     *
     * @Assert\NotBlank
     * @Assert\Length(max="255")
     */
    private string $riskGroupName;

    /**
     * @ORM\Column(length=8, nullable=true)
     *
     * @Assert\Choice(callback={CAInternalRating::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $internalRatingScore;

    /**
     * @ORM\Column(length=191)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="191")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private string $title;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private Money $globalFundingMoney;

    /**
     * This collection will be indexed by secondary.
     * This is either true or false. False means primary and true means secondary.
     *
     * @var Collection|ParticipationPool[]
     *
     * @ORM\OneToMany(targetEntity=ParticipationPool::class, mappedBy="project", indexBy="secondary", cascade={"persist", "remove"})
     *
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     * @Assert\Valid
     * @Assert\Count(min=2, max=2)
     */
    private Collection $participationPools;

    /**
     * @var Collection|Tranche[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Agency\Entity\Tranche", mappedBy="project", orphanRemoval=true, cascade={"persist", "remove"})
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     *
     * @Assert\Count(min="1", groups={"published"})
     *
     * TODO Create custom endpoint to handle security
     * @ApiSubresource
     */
    private Collection $tranches;

    /**
     * @var Borrower[]|Collection
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Agency\Entity\Borrower", mappedBy="project", orphanRemoval=true, cascade={"persist", "remove"})
     *
     * @Assert\Valid
     * @Assert\Count(min="1", groups={"published"})
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     *
     * @ApiSubresource
     */
    private Collection $borrowers;

    /**
     * @Groups({"agency:project:read", "agency:project:write"})
     *
     * @ORM\Column(type="string", nullable=true, length=10)
     *
     * @Assert\Choice(callback={FundingSpecificity::class, "getConstList"})
     */
    private ?string $fundingSpecificity;

    /**
     * @ORM\ManyToOne(targetEntity=CompanyGroupTag::class)
     * @ORM\JoinColumn(name="id_company_group_tag", referencedColumnName="id")
     *
     * Remove assertion for external banks (they may have no companyGroupTag)
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?CompanyGroupTag $companyGroupTag;

    /**
     * Date de signature.
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private DateTimeImmutable $closingDate;

    /**
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\GreaterThanOrEqual(propertyPath="closingDate")
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private DateTimeImmutable $contractEndDate;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={Project::class, "getAvailableStatuses"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private int $currentStatus;

    /**
     * @var iterable|ProjectStatusHistory[]
     *
     * @ORM\OneToMany(targetEntity="ProjectStatusHistory", orphanRemoval=true, mappedBy="project", cascade={"persist", "remove"})
     *
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     * @Assert\Valid
     */
    private iterable $statuses;

    /**
     * @var Collection|Covenant[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Agency\Entity\Covenant", mappedBy="project", cascade={"persist"}, fetch="EAGER")
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     *
     * @Assert\Valid(groups={"Default", "Project"})
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     */
    private Collection $covenants;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_borrower_shared_drive", nullable=false, unique=true)
     */
    private Drive $borrowerSharedDrive;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_borrower_confidential_drive", nullable=false, unique=true)
     */
    private Drive $borrowerConfidentialDrive;

    /**
     * @ORM\ManyToOne(targetEntity=ArrangementProject::class, cascade={"persist"})
     * @ORM\JoinColumn(name="id_arrangement_project", nullable=true, onDelete="SET NULL")
     *
     * @Assert\Expression("value === null || value.isFinished()")
     */
    private ?ArrangementProject $source;

    /**
     * Date de cloture anticipée.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isFinished()"),
     *     @Assert\IsNull
     * })
     *
     * @Groups({"agency:project:write"})
     */
    private ?DateTimeImmutable $anticipatedFinishDate;

    /**
     * @throws Exception
     */
    public function __construct(
        Staff $addedBy,
        string $title,
        string $riskGroupName,
        Money $globalFundingMoney,
        DateTimeImmutable $closingDate,
        DateTimeImmutable $contractEndDate,
        ?ArrangementProject $source = null
    ) {
        $currentUser = $addedBy->getUser();

        $this->added   = new DateTimeImmutable();
        $this->addedBy = $addedBy;

        $this->riskGroupName      = $riskGroupName;
        $this->globalFundingMoney = $globalFundingMoney;
        $this->closingDate        = $closingDate;
        $this->contractEndDate    = $contractEndDate;
        $this->title              = $title;

        $this->borrowers          = new ArrayCollection();
        $this->tranches           = new ArrayCollection();
        $this->covenants          = new ArrayCollection();
        $this->participationPools = new ArrayCollection([false => new ParticipationPool($this, false), true => new ParticipationPool($this, true)]);

        $this->agent = new Agent($this, $addedBy->getCompany());
        $this->agent->addMember(new AgentMember($this->agent, $addedBy->getUser()));
        $this->agent->setContact(
            (new NullablePerson())
                ->setFirstName($currentUser->getFirstName())
                ->setLastName($currentUser->getLastName())
                ->setEmail($currentUser->getEmail())
                ->setPhone($currentUser->getPhone())
        );

        $participation = new Participation(
            $this->getPrimaryParticipationPool(),
            $this->agent->getCompany()
        );
        $participation->setAgentCommission(new NullableMoney($this->getCurrency(), '0'));

        $this->participationPools[false]->addParticipation($participation);

        $this->statuses      = new ArrayCollection();
        $this->currentStatus = static::STATUS_DRAFT;

        $this->borrowerConfidentialDrive = new Drive();
        $this->borrowerSharedDrive       = new Drive();

        $this->anticipatedFinishDate = null;
        $this->covenants             = new ArrayCollection();

        $this->source = $source;
        if ($source) {
            $source->setAgencyImported(true);
        }
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function getAgentCompany(): Company
    {
        return $this->agent->getCompany();
    }

    public function setRiskGroupName(string $riskGroupName): Project
    {
        $this->riskGroupName = $riskGroupName;

        return $this;
    }

    public function getRiskGroupName(): string
    {
        return $this->riskGroupName;
    }

    public function getInternalRatingScore(): ?string
    {
        return $this->internalRatingScore;
    }

    public function setInternalRatingScore(?string $internalRatingScore): Project
    {
        $this->internalRatingScore = $internalRatingScore;

        return $this;
    }

    /**
     * @Groups({"agency:project:read"})
     */
    public function hasSilentSyndication(): bool
    {
        return false === $this->getSecondaryParticipationPool()->isEmpty();
    }

    /**
     * @return Borrower[]|Collection
     */
    public function getBorrowers(): Collection
    {
        return $this->borrowers;
    }

    /**
     * @param Borrower[]|Collection $borrowers
     */
    public function setBorrowers(Collection $borrowers): Project
    {
        $this->borrowers = $borrowers;

        return $this;
    }

    public function addBorrower(Borrower $borrower): Project
    {
        $this->borrowers[] = $borrower;

        return $this;
    }

    public function removeBorrower(Borrower $borrower): Project
    {
        $this->borrowers->removeElement($borrower);

        return $this;
    }

    /**
     * @return Collection|Tranche[]
     */
    public function getTranches()
    {
        return $this->tranches;
    }

    /**
     * @return Project
     */
    public function addTranche(Tranche $tranche)
    {
        // There is no unicity factor in tranche so I cannot use exists
        if (false === $this->tranches->contains($tranche)) {
            $this->tranches->add($tranche);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function removeTranche(Tranche $tranche)
    {
        $this->tranches->removeElement($tranche);

        return $this;
    }

    /**
     * @param iterable|Tranche[] $tranches
     */
    public function setTranches($tranches): Project
    {
        $this->tranches = $tranches;

        return $this;
    }

    public function setTitle(string $title): Project
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setGlobalFundingMoney(Money $globalFundingMoney): Project
    {
        $this->globalFundingMoney = $globalFundingMoney;

        return $this;
    }

    public function getGlobalFundingMoney(): Money
    {
        return $this->globalFundingMoney;
    }

    public function getFundingSpecificity(): ?string
    {
        return $this->fundingSpecificity;
    }

    public function setFundingSpecificity(?string $fundingSpecificity): Project
    {
        $this->fundingSpecificity = $fundingSpecificity;

        return $this;
    }

    public function getCompanyGroupTag(): ?CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

    public function setCompanyGroupTag(?CompanyGroupTag $companyGroupTag): Project
    {
        $this->companyGroupTag = $companyGroupTag;

        return $this;
    }

    public function getClosingDate(): DateTimeImmutable
    {
        return $this->closingDate;
    }

    public function setClosingDate(DateTimeImmutable $closingDate): Project
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    public function getContractEndDate(): DateTimeImmutable
    {
        return $this->contractEndDate;
    }

    public function setContractEndDate(DateTimeImmutable $contractEndDate): Project
    {
        $this->contractEndDate = $contractEndDate;

        return $this;
    }

    public function setDescription(?string $description): Project
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return iterable|Participation[]
     *
     * @ApiProperty(security="is_granted('agent', object)")
     */
    public function getParticipations(): Generator
    {
        foreach ($this->participationPools as $pool) {
            yield from $pool->getParticipations();
        }
    }

    public function addParticipation(Participation $participation): Project
    {
        if ($this->findParticipationByParticipant($participation->getParticipant())) {
            return $this;
        }

        $this->participationPools[$participation->getPool()->isSecondary()]->addParticipation($participation);

        return $this;
    }

    /**
     * @return ArrayCollection|Collection|ParticipationPool[]
     */
    public function getParticipationPools(): Collection
    {
        return $this->participationPools;
    }

    /**
     * @ApiProperty(security="is_granted('agent', object) || is_granted('borrower', object) || is_granted('primary_participant', object)")
     *
     * @Groups({"agency:project:read"})
     */
    public function getPrimaryParticipationPool(): ParticipationPool
    {
        return $this->participationPools[false];
    }

    /**
     * @ApiProperty(security="is_granted('agent', object) || is_granted('secondary_participant', object)")
     *
     * @Groups({"agency:project:read"})
     */
    public function getSecondaryParticipationPool(): ParticipationPool
    {
        return $this->participationPools[true];
    }

    public function getCurrentStatus(): int
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus(int $currentStatus): Project
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return iterable|ProjectStatusHistory[]
     */
    public function getStatuses(): iterable
    {
        return $this->statuses;
    }

    /**
     * @return Collection|Covenant[]
     */
    public function getCovenants(): Collection
    {
        return $this->covenants;
    }

    public function addCovenant(Covenant $covenants): Project
    {
        $this->covenants->add($covenants);

        return $this;
    }

    public function removeCovenant(Covenant $covenants): Project
    {
        $this->covenants->removeElement($covenants);

        return $this;
    }

    public function isEditable(): bool
    {
        return false === $this->isArchived() && false === $this->isFinished();
    }

    public function isPublished(): bool
    {
        return static::STATUS_PUBLISHED === $this->currentStatus;
    }

    public function isArchived(): bool
    {
        return static::STATUS_ARCHIVED === $this->currentStatus;
    }

    public function isFinished(): bool
    {
        return static::STATUS_FINISHED === $this->currentStatus;
    }

    public function isDraft(): bool
    {
        return static::STATUS_DRAFT === $this->currentStatus;
    }

    public function publish(): Project
    {
        if ($this->isDraft()) {
            $this->currentStatus = static::STATUS_PUBLISHED;
        }

        return $this;
    }

    public function archive(): Project
    {
        if (false === $this->isDraft()) {
            $this->currentStatus = static::STATUS_ARCHIVED;
        }

        return $this;
    }

    public function finish(): Project
    {
        if (false === $this->isDraft()) {
            $this->currentStatus = static::STATUS_FINISHED;
        }

        return $this;
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups.
     *
     * @param Project $project
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(self $project): array
    {
        $validationGroups = ['Default', 'Project'];

        if ($project->isPublished()) {
            $validationGroups[] = 'published';
        }

        return $validationGroups;
    }

    public function getBorrowerSharedDrive(): Drive
    {
        return $this->borrowerSharedDrive;
    }

    public function getBorrowerConfidentialDrive(): Drive
    {
        return $this->borrowerConfidentialDrive;
    }

    /**
     * @Groups({"agency:project:read"})
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     */
    public function getAgentParticipation(): Participation
    {
        return $this->findParticipationByParticipant($this->getAgentCompany());
    }

    public function findParticipationByParticipant(Company $participant): ?Participation
    {
        foreach ($this->getParticipations() as $participation) {
            if ($participation->getParticipant() === $participant) {
                return $participation;
            }
        }

        return null;
    }

    public function getSource(): ?ArrangementProject
    {
        return $this->source;
    }

    public function getCurrency(): string
    {
        return $this->getGlobalFundingMoney()->getCurrency();
    }

    /**
     * @return array|int[]
     */
    public static function getAvailableStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    public function getAnticipatedFinishDate(): ?DateTimeImmutable
    {
        return $this->anticipatedFinishDate;
    }

    public function setAnticipatedFinishDate(?DateTimeImmutable $anticipatedFinishDate): Project
    {
        $this->anticipatedFinishDate = $anticipatedFinishDate;

        return $this;
    }

    public function getParticipants(): Generator
    {
        foreach ($this->getParticipations() as $participation) {
            yield $participation->getParticipant();
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateParticipants(ExecutionContextInterface $context)
    {
        $participants = \iterator_to_array($this->getParticipants(), false);

        $sirens = \array_map(static fn (Company $company) => $company->getSiren(), $participants);

        $sirens = \array_count_values($sirens);

        $duplicatedSirens = \array_filter($sirens, static fn ($count) => $count > 1);

        foreach ($duplicatedSirens as $siren => $count) {
            $context->buildViolation('Agency.Project.participations.duplicate')
                ->setParameter('siren', (string) $siren)
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateStatusEntry(ExecutionContextInterface $context)
    {
        if (
            ($this->currentStatus > 0)
            && $this->statuses->exists(fn ($key, ProjectStatusHistory $statusHistory) => $statusHistory->getStatus() > $this->currentStatus)
        ) {
            $context->buildViolation('Agency.Project.passedStatus')
                ->setParameter('status', (string) $this->currentStatus)
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateStatusTransition(ExecutionContextInterface $context)
    {
        $statuses = [];

        switch ($this->currentStatus) {
            case static::STATUS_DRAFT:
                $statuses = [];

                break;

            case static::STATUS_ARCHIVED:
            case static::STATUS_FINISHED:
                $statuses = [static::STATUS_PUBLISHED];

                break;

            case static::STATUS_PUBLISHED:
                $statuses = [static::STATUS_DRAFT];

                break;
        }

        \sort($statuses);

        \reset($statuses);

        while (($status = \current($statuses))) {
            if (false === $this->statuses->exists(fn ($_, ProjectStatusHistory $history) => $history->getStatus() === $status)) {
                $context->buildViolation('Agency.Project.missingStatus', [
                    '{{ missingStatus }}' => $status,
                    '{{ nextStatus }}'    => $this->currentStatus,
                ])->addViolation();
            }

            \next($statuses);
        }
    }

    public function findBorrowerBySiren(string $siren): ?Borrower
    {
        return $this->borrowers->filter(fn (Borrower $borrower) => $borrower->getMatriculationNumber() === $siren)->first() ?: null;
    }

    /**
     * @return iterable|AbstractProjectMember[]
     */
    public function getMembers(): iterable
    {
        yield from $this->getAgent()->getMembers();

        foreach ($this->getBorrowers() as $borrower) {
            yield from $borrower->getMembers();
        }

        foreach ($this->getParticipations() as $participation) {
            yield from $participation->getMembers();
        }
    }

    public function getArrangerParticipation(): ?Participation
    {
        foreach ($this->getParticipations() as $participation) {
            if ($participation->isArranger()) {
                return $participation;
            }
        }

        return null;
    }
}
