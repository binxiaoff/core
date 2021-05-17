<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

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
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Agency\Controller\Project\GetTerm;
use Unilend\Agency\Entity\Versioned\VersionedProject;
use Unilend\Agency\Filter\ApiPlatform\ProjectFilter;
use Unilend\Core\Controller\Dataroom\Delete;
use Unilend\Core\Controller\Dataroom\Get;
use Unilend\Core\Controller\Dataroom\Post;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\FundingSpecificity;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Embeddable\NullablePerson;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Model\Bitmask;
use Unilend\Core\Traits\ConstantsAwareTrait;
use Unilend\Syndication\Entity\Project as ArrangementProject;

/**
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
 *             "controller": GetTerm::class
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
 *                 "drive": "shared"
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
 *                 "drive": "shared"
 *             },
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             }
 *         },
 *         "delete_borrower_dataroom_shared": {
 *             "method": "DELETE",
 *             "path": "/agency/projects/{publicId}/borrowers/dataroom/shared/{path?}",
 *             "security": "is_granted('agent', object) || is_granted('borrower', object)",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "shared"
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
 *                 "drive": "confidential"
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
 *                 "drive": "confidential"
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
 *                 "drive": "confidential"
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
 *             "agency:contact:read",
 *             "agency:borrower:read",
 *             "agency:tranche:read",
 *             "agency:borrowerTrancheShare:read",
 *             "agency:participation:read",
 *             "agency:participationPool:read",
 *             "agency:participationTrancheAllocation:read",
 *             "company:read",
 *             "companyGroupTag:read",
 *             "agency:covenant:read",
 *             "agency:term:read"
 *         }
 *     }
 * )
 *
 * @ApiFilter(filterClass=NumericFilter::class, properties={"currentStatus"})
 * @ApiFilter(filterClass=ProjectFilter::class, arguments={})
 */
class Project
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use ConstantsAwareTrait;

    public const STATUS_DRAFT     = 10;
    public const STATUS_PUBLISHED = 20;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_agent", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"agency:project:read"})
     *
     * @Assert\NotBlank
     */
    private Company $agent;

    /**
     * @ORM\Column(type="string", length=300, nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentDisplayName;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentLegalForm;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $headOffice;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $bankInstitution;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Bic
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $bic;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     *
     * @Assert\Iban
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $iban;

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
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private Money $globalFundingMoney;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private bool $silentSyndication;

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
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Tranche", mappedBy="project", orphanRemoval=true, cascade={"persist", "remove"})
     *
     * @Groups({"agency:project:read"})
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     *
     * @ApiSubresource
     */
    private Collection $tranches;

    /**
     * @var Borrower[]|iterable
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Borrower", mappedBy="project", orphanRemoval=true)
     *
     * @Groups({"agency:project:read"})
     *
     * @MaxDepth(1)
     *
     * @Assert\Valid
     * @Assert\Count(min="1", groups={"published"})
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     */
    private iterable $borrowers;

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
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_group_tag", referencedColumnName="id")
     * })
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
     * @ORM\Column(type="date_immutable")
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private DateTimeImmutable $closingDate;

    /**
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\GreaterThan(propertyPath="closingDate")
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
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullablePerson", columnPrefix="agency_contact_")
     *
     * @Assert\Valid
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private NullablePerson $agencyContact;

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
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Covenant", mappedBy="project", cascade={"persist"}, fetch="EAGER")
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     *
     * @Assert\Valid(groups={"Default", "Project"})
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     *
     * @ApiSubresource
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
     * @throws \Exception
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
        $this->added   = new DateTimeImmutable();
        $this->addedBy = $addedBy;
        $this->agent   = $addedBy->getCompany();

        $currentUser         = $addedBy->getUser();
        $this->agencyContact = (new NullablePerson())
            ->setFirstName($currentUser->getFirstName())
            ->setLastName($currentUser->getLastName())
            ->setEmail($currentUser->getEmail())
            ->setPhone($currentUser->getPhone())
        ;

        $this->riskGroupName      = $riskGroupName;
        $this->globalFundingMoney = $globalFundingMoney;
        $this->closingDate        = $closingDate;
        $this->contractEndDate    = $contractEndDate;
        $this->title              = $title;

        $this->borrowers          = new ArrayCollection();
        $this->tranches           = new ArrayCollection();
        $this->participationPools = new ArrayCollection([false => new ParticipationPool($this, false), true => new ParticipationPool($this, true)]);

        $participation = new Participation(
            $this->getPrimaryParticipationPool(),
            $this->agent,
            new Money($this->globalFundingMoney->getCurrency()),
            new Money($this->globalFundingMoney->getCurrency())
        );

        $participation->setResponsibilities(new Bitmask(Participation::RESPONSIBILITY_AGENT));
        $participation->setAgentCommission('0');
        $participation->setMembers(new ArrayCollection([new ParticipationMember($participation, $addedBy->getUser())]));

        $this->participationPools[false]->addParticipation($participation);

        $this->silentSyndication = false;

        $this->statuses      = new ArrayCollection();
        $this->currentStatus = static::STATUS_DRAFT;

        // This part is weird but compliant to figma models: those fields are editable
        $this->agentDisplayName = $this->agent->getDisplayName();

        $this->borrowerConfidentialDrive = new Drive();
        $this->borrowerSharedDrive       = new Drive();

        $this->source = $source;
        if ($source) {
            $source->setAgencyImported(true);
        }
    }

    public function getAgent(): Company
    {
        return $this->agent;
    }

    public function getAgentDisplayName(): ?string
    {
        return $this->agentDisplayName;
    }

    public function setAgentDisplayName(?string $agentDisplayName): Project
    {
        $this->agentDisplayName = $agentDisplayName;

        return $this;
    }

    public function getAgentLegalForm(): ?string
    {
        return $this->agentLegalForm;
    }

    public function setAgentLegalForm(?string $agentLegalForm): Project
    {
        $this->agentLegalForm = $agentLegalForm;

        return $this;
    }

    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    public function setHeadOffice(?string $headOffice): Project
    {
        $this->headOffice = $headOffice;

        return $this;
    }

    public function getAgencyContact(): NullablePerson
    {
        return $this->agencyContact;
    }

    public function setAgencyContact(NullablePerson $agencyContact): Project
    {
        $this->agencyContact = $agencyContact;

        return $this;
    }

    public function getBankInstitution(): ?string
    {
        return $this->bankInstitution;
    }

    public function setBankInstitution(?string $bankInstitution): Project
    {
        $this->bankInstitution = $bankInstitution;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): Project
    {
        $this->bic = $bic;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): Project
    {
        $this->iban = $iban;

        return $this;
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

    public function hasSilentSyndication(): bool
    {
        return $this->silentSyndication;
    }

    public function setSilentSyndication(bool $silentSyndication): Project
    {
        $this->silentSyndication = $silentSyndication;

        return $this;
    }

    /**
     * @return Borrower[]|iterable
     */
    public function getBorrowers(): iterable
    {
        return $this->borrowers;
    }

    /**
     * @param Borrower[]|iterable $borrowers
     *
     * @return Project
     */
    public function setBorrowers(iterable $borrowers)
    {
        $this->borrowers = $borrowers;

        return $this;
    }

    /**
     * @return iterable|Tranche[]
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
     * @Assert\Count(min="1", groups={"published"})
     *
     * @return Tranche[]|iterable
     */
    public function getSyndicatedTranches(): iterable
    {
        return $this->tranches->filter(fn (Tranche $tranche) => $tranche->isSyndicated());
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
     */
    public function getParticipations(): iterable
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
     * @ApiProperty(security="is_granted('agent', object)")
     *
     * @Groups({"agency:project:read"})
     */
    public function getPrimaryParticipationPool(): ParticipationPool
    {
        return $this->participationPools[false];
    }

    /**
     * @ApiProperty(security="is_granted('agent', object)")
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
     * @return iterable|Covenant[]
     */
    public function getCovenants(): iterable
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

    public function isPublished(): bool
    {
        return static::STATUS_PUBLISHED === $this->currentStatus;
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

    public function getAgentParticipation(): Participation
    {
        return $this->findParticipationByParticipant($this->getAgent());
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

    /**
     * @return array|int[]
     */
    public static function getAvailableStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * @param $payload
     *
     * @Assert\Callback
     */
    public function validateStatusTransition(ExecutionContextInterface $context, $payload)
    {
        $statuses = array_values(static::getAvailableStatuses());

        sort($statuses);

        reset($statuses);

        while (($status = current($statuses)) && $status < $this->currentStatus) {
            if (false === $this->statuses->exists(fn ($_, ProjectStatusHistory $history) => $history->getStatus() === $status)) {
                $context->buildViolation('Agency.Project.missingStatus', [
                    'missingStatus' => $status,
                    'nextStatus'    => $this->currentStatus,
                ])->addViolation();
            }

            next($statuses);
        }
    }
}
