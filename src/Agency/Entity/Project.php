<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Agency\Controller\Project\GetTerm;
use Unilend\Agency\Entity\Versioned\VersionedProject;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\FundingSpecificity;
use Unilend\Core\Entity\Constant\SyndicationModality\ParticipationType;
use Unilend\Core\Entity\Constant\SyndicationModality\RiskType;
use Unilend\Core\Entity\Constant\SyndicationModality\SyndicationType;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Embeddable\NullablePerson;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Model\Bitmask;
use Unilend\Core\Validator\Constraints\Siren;

/**
 * @ApiResource(
 *     attributes={
 *         "validation_groups": {Project::class, "getCurrentValidationGroups"}
 *     },
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
 *                     "agency:projectStatus:create",
 *                     "money:write",
 *                     "nullablePerson:write",
 *                     "nullableMoney:write",
 *                     "agency:covenant:update"
 *                 }
 *             },
 *             "validation_groups": {Project::class, "getCurrentValidationGroups"}
 *         },
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
 *             "agency:participationTrancheAllocation:read",
 *             "company:read",
 *             "companyGroupTag:read",
 *             "agency:covenant:read",
 *             "agency:term:read"
 *         }
 *     }
 * )
 */
class Project
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;

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
     * @ORM\Column(type="string", length=9, nullable=true)
     *
     * @Siren
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentSiren;

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
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?NullableMoney $agentCapital;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentRCS;

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
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={SyndicationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $principalSyndicationType;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $principalParticipationType;

    /**
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(false === this.isPrincipalSubParticipation() and null === value) or (this.isPrincipalSubParticipation() and value)",
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $principalRiskType;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={SyndicationType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(this.hasSilentSyndication() and value) or (false === this.hasSilentSyndication() and null === value)",
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $secondarySyndicationType;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(this.hasSilentSyndication() and value) or (false === this.hasSilentSyndication() and null === value)",
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $secondaryParticipationType;

    /**
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(false === this.isSecondarySubParticipation() and null === value) or (this.isSecondarySubParticipation() and value)",
     *     groups={"published"}
     * ),
     * @Assert\Expression(
     * expression="(this.hasSilentSyndication()) or (false === this.hasSilentSyndication() and null === value)"),
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read"})
     */
    private ?string $secondaryRiskType;

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
     *     @ORM\JoinColumn(name="id_company_group_tag", referencedColumnName="id", nullable=false)
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
     * @var Participation[]|iterable
     *
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="project", orphanRemoval=true, cascade={"persist", "remove"})
     *
     * @Groups({"agency:project:read"})
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     */
    private iterable $participations;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Agency\Entity\ProjectStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     * @Assert\Expression("this === value.getProject()")
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ProjectStatus $currentStatus;

    /**
     * @var iterable|ProjectStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\ProjectStatus", orphanRemoval=true, cascade={"persist"}, mappedBy="project", fetch="EAGER")
     *
     * @Assert\Count(min="1")
     */
    private iterable $statuses;

    /**
     * @var Collection|Covenant[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Covenant", mappedBy="project", cascade={"persist"}, fetch="EAGER")
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getProject() === this")
     * })
     *
     * @ApiSubresource
     */
    private Collection $covenants;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_agent_borrower_drive", nullable=false, unique=true)
     */
    private Drive $agentBorrowerDrive;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_agent_principal_borrower_drive", nullable=false, unique=true)
     */
    private Drive $agentPrincipalParticipantDrive;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_agent_secondary_borrower_drive", nullable=false, unique=true)
     */
    private Drive $agentSecondaryParticipantDrive;

    /**
     * There is no direct relation with Arrangement project because there is no need and it would add an uneeded relation.
     *
     * @ORM\Column(type="string", length=36, nullable=true)
     */
    private ?string $sourcePublicId;

    /**
     * @throws Exception
     */
    public function __construct(
        Staff $addedBy,
        string $title,
        string $riskGroupName,
        Money $globalFundingMoney,
        DateTimeImmutable $closingDate,
        DateTimeImmutable $contractEndDate
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

        $this->borrowers = new ArrayCollection();
        $this->tranches  = new ArrayCollection();
        $participation   = new Participation($this, $this->agent, new Money($this->globalFundingMoney->getCurrency()));
        $participation->setResponsibilities(new Bitmask(Participation::RESPONSIBILITY_AGENT));
        $participation->setAgentCommission('0');
        $participation->setMembers(new ArrayCollection([new ParticipationMember($participation, $addedBy->getUser())]));
        $this->participations = new ArrayCollection([$participation]);

        $this->silentSyndication = false;

        $this->principalSyndicationType   = null;
        $this->principalParticipationType = null;
        $this->principalRiskType          = null;

        $this->secondarySyndicationType   = null;
        $this->secondaryParticipationType = null;
        $this->secondaryRiskType          = null;

        $this->currentStatus = new ProjectStatus($this, $addedBy, ProjectStatus::DRAFT);
        $this->statuses      = new ArrayCollection([$this->currentStatus]);

        // This part is weird but compliant to figma models: those fields are editable
        $this->agentDisplayName = $this->agent->getDisplayName();
        $this->agentSiren       = $this->agent->getSiren();

        $this->agentBorrowerDrive             = new Drive();
        $this->agentPrincipalParticipantDrive = new Drive();
        $this->agentSecondaryParticipantDrive = new Drive();

        $this->sourcePublicId = null;
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

    public function getAgentSiren(): ?string
    {
        return $this->agentSiren;
    }

    public function setAgentSiren(?string $agentSiren): Project
    {
        $this->agentSiren = $agentSiren;

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

    public function getAgentCapital(): ?NullableMoney
    {
        return $this->agentCapital;
    }

    public function setAgentCapital(?NullableMoney $agentCapital): Project
    {
        $this->agentCapital = $agentCapital;

        return $this;
    }

    public function getAgentRCS(): ?string
    {
        return $this->agentRCS;
    }

    public function setAgentRCS(?string $agentRCS): Project
    {
        $this->agentRCS = $agentRCS;

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

    /**
     * @return Company
     */
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

    public function getPrincipalSyndicationType(): ?string
    {
        return $this->principalSyndicationType;
    }

    public function setPrincipalSyndicationType(?string $principalSyndicationType): Project
    {
        $this->principalSyndicationType = $principalSyndicationType;

        return $this;
    }

    public function getPrincipalParticipationType(): ?string
    {
        return $this->principalParticipationType;
    }

    public function setPrincipalParticipationType(?string $principalParticipationType): Project
    {
        $this->principalParticipationType = $principalParticipationType;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrincipalSubParticipation()
    {
        return ParticipationType::SUB_PARTICIPATION === $this->principalParticipationType;
    }

    public function getPrincipalRiskType(): ?string
    {
        return $this->principalRiskType;
    }

    public function setPrincipalRiskType(?string $principalRiskType): Project
    {
        $this->principalRiskType = $principalRiskType;

        return $this;
    }

    public function getSecondarySyndicationType(): ?string
    {
        return $this->secondarySyndicationType;
    }

    public function setSecondarySyndicationType(?string $secondarySyndicationType): Project
    {
        $this->secondarySyndicationType = $secondarySyndicationType;

        return $this;
    }

    public function getSecondaryParticipationType(): ?string
    {
        return $this->secondaryParticipationType;
    }

    /**
     * @return bool
     */
    public function isSecondarySubParticipation()
    {
        return ParticipationType::SUB_PARTICIPATION === $this->secondaryParticipationType;
    }

    public function setSecondaryParticipationType(?string $secondaryParticipationType): Project
    {
        $this->secondaryParticipationType = $secondaryParticipationType;

        return $this;
    }

    public function getSecondaryRiskType(): ?string
    {
        return $this->secondaryRiskType;
    }

    public function setSecondaryRiskType(?string $secondaryRiskType): Project
    {
        $this->secondaryRiskType = $secondaryRiskType;

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
        $this->tranches->add($tranche);

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
     *
     * @return Project
     */
    public function setTranches($tranches)
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
    public function getParticipations()
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): Project
    {
        $this->participations->add($participation);

        return $this;
    }

    public function removeParticipation(Participation $participation): Project
    {
        $this->participations->removeElement($participation);

        return $this;
    }

    public function getCurrentStatus(): ProjectStatus
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus(ProjectStatus $currentStatus): Project
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return iterable|ProjectStatus[]
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
        return ProjectStatus::DRAFT > $this->getCurrentStatus()->getStatus();
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

    public function getAgentBorrowerDrive(): Drive
    {
        return $this->agentBorrowerDrive;
    }

    public function getAgentPrincipalParticipantDrive(): Drive
    {
        return $this->agentPrincipalParticipantDrive;
    }

    public function getAgentSecondaryParticipantDrive(): Drive
    {
        return $this->agentSecondaryParticipantDrive;
    }

    public function getAgentParticipation(): Participation
    {
        return $this->findParticipationByParticipant($this->getAgent());
    }

    public function findParticipationByParticipant(Company $participant): ?Participation
    {
        return $this->participations->filter(fn (Participation $participation) => $participation->getParticipant() === $participant)->first() ?: null;
    }

    public function getSourcePublicId(): ?string
    {
        return $this->sourcePublicId;
    }

    public function setSourcePublicId(?string $sourcePublicId): Project
    {
        $this->sourcePublicId = $sourcePublicId;

        return $this;
    }
}
