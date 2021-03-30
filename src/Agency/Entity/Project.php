<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Agency\Controller\Project\GetTerm;
use Unilend\Core\Entity\Constant\SyndicationModality\{ParticipationType, RiskType, SyndicationType};
use Unilend\Core\Entity\Constant\{CAInternalRating, FundingSpecificity};
use Unilend\Core\Entity\Embeddable\{Money, NullableMoney, NullablePerson};
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Entity\{Company, CompanyGroupTag, Drive, Staff};
use Unilend\Core\Model\Bitmask;
use Unilend\Core\Validator\Constraints\Siren;

/**
 * @ApiResource(
 *     attributes={
 *             "validation_groups": {Project::class, "getCurrentValidationGroups"}
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
 *                  "groups": {
 *                       "agency:project:create",
 *                       "money:write",
 *                       "nullablePerson:write",
 *                       "nullableMoney:write"
 *                  }
 *             },
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
 *                  "groups": {
 *                      "agency:project:write",
 *                      "agency:projectStatus:create",
 *                      "money:write",
 *                      "nullablePerson:write",
 *                      "nullableMoney:write",
 *                      "agency:covenant:update"
 *                  }
 *             },
 *             "validation_groups": {Project::class, "getCurrentValidationGroups"}
 *         },
 *     }
 * )
 *
 * @ORM\Table(name="agency_project")
 * @ORM\Entity
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Agency\Entity\Versioned\VersionedProject")
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
 *             "agency:covenant:read",
 *             "agency:term:read"
 *         }
 *    }
 * )
 */
class Project
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_agent", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"agency:project:read"})
     *
     * @Assert\NotBlank()
     */
    private Company $agent;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=300, nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentDisplayName;

    /**
     * @var string|null
     *
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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentLegalForm;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $headOffice;

    /**
     * @var NullableMoney|null
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?NullableMoney $agentCapital;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $agentRCS;

    /**
     * @var Contact[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Contact", mappedBy="project", orphanRemoval=true, cascade={"remove"})
     *
     * @Assert\All({
     *    @Assert\Expression("value.getProject() === this")
     * })
     */
    private Collection $contacts;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $bankInstitution;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Bic
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:read", "agency:project:write"})
     */
    private ?string $bic;

    /**
     * @var string|null
     *
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
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     *
     * @Assert\NotBlank
     * @Assert\Length(max="255")
     */
    private string $riskGroupName;

    /**
     * @var string|null
     *
     * @ORM\Column(length=8, nullable=true)
     *
     * @Assert\Choice(callback={CAInternalRating::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $internalRatingScore;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="191")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private string $title;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:project:read", "agency:project:write", "agency:project:create"})
     */
    private Money $globalFundingMoney;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private bool $silentSyndication;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={SyndicationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $principalSyndicationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $principalParticipationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(false === this.isPrincipalSubParticipation() and null === value) or (this.isPrincipalSubParticipation() and value)",
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $principalRiskType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={SyndicationType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(this.hasSilentSyndication() and value) or (false === this.hasSilentSyndication() and null === value)",
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $secondarySyndicationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(this.hasSilentSyndication() and value) or (false === this.hasSilentSyndication() and null === value)",
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $secondaryParticipationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(false === this.isSecondarySubParticipation() and null === value) or (this.isSecondarySubParticipation() and value)",
     *     groups={"published"}
     * ),
     * @Assert\Expression(
     *     expression="(this.hasSilentSyndication()) or (false === this.hasSilentSyndication() and null === value)"),
     *     groups={"published"}
     * )
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $secondaryRiskType;

    /**
     * @var iterable|Tranche[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Tranche", mappedBy="project", orphanRemoval=true, cascade={"persist", "remove"})
     *
     * @Groups({"agency:project:read"})
     *
     * @Assert\Valid
     * @Assert\All({
     *    @Assert\Expression("value.getProject() === this")
     * })
     *
     * @ApiSubresource
     */
    private iterable $tranches;

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
     *    @Assert\Expression("value.getProject() === this")
     * })
     */
    private iterable $borrowers;

    /**
     * @var string|null
     *
     * @Groups({"agency:project:read", "agency:project:write", "agency:project:create"})
     *
     * @ORM\Column(type="string", nullable=true, length=10)
     *
     * @Assert\Choice(callback={FundingSpecificity::class, "getConstList"})
     */
    private ?string $fundingSpecificity;

    /**
     * @var CompanyGroupTag|null
     *
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
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?CompanyGroupTag $companyGroupTag;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private DateTimeImmutable $closingDate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\GreaterThan(propertyPath="closingDate")
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private DateTimeImmutable $contractEndDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Groups({"agency:project:write", "agency:project:read", "agency:project:create"})
     */
    private ?string $description = null;

    /**
     * @var NullablePerson
     *
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
     *    @Assert\Expression("value.getProject() === this")
     * })
     */
    private iterable $participations;

    /**
     * @var ProjectStatus
     *
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
     *    @Assert\Expression("value.getProject() === this")
     * })
     *
     * @ApiSubresource()
     */
    private Collection $covenants;

    /**
     * @var Drive
     *
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_agent_borrower_drive", nullable=false, unique=true)
     */
    private Drive $agentBorrowerDrive;

    /**
     * @var Drive
     *
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_agent_principal_borrower_drive", nullable=false, unique=true)
     */
    private Drive $agentPrincipalParticipantDrive;

    /**
     * @var Drive
     *
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_agent_secondary_borrower_drive", nullable=false, unique=true)
     */
    private Drive $agentSecondaryParticipantDrive;

    /**
     * @param Staff             $addedBy
     * @param string            $title
     * @param string            $riskGroupName
     * @param Money             $globalFundingMoney
     * @param DateTimeImmutable $closingDate
     * @param DateTimeImmutable $contractEndDate
     *
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
        $this->added              = new DateTimeImmutable();
        $this->addedBy            = $addedBy;
        $this->agent              = $addedBy->getCompany();

        $currentUser         = $addedBy->getUser();
        $this->agencyContact = (new NullablePerson())
            ->setFirstName($currentUser->getFirstName())
            ->setLastName($currentUser->getLastName())
            ->setEmail($currentUser->getEmail())
            ->setPhone($currentUser->getPhone());

        $this->contacts           = new ArrayCollection();
        $this->riskGroupName      = $riskGroupName;
        $this->globalFundingMoney = $globalFundingMoney;
        $this->closingDate        = $closingDate;
        $this->contractEndDate    = $contractEndDate;
        $this->title              = $title;

        $this->borrowers = new ArrayCollection();
        $this->tranches  = new ArrayCollection();
        $participation = new Participation($this, $this->agent, new Money($this->globalFundingMoney->getCurrency()));
        $participation->setResponsibilities(new Bitmask(Participation::RESPONSIBILITY_AGENT));
        $participation->setAgentCommission('0');
        $this->participations = new ArrayCollection([$participation]);

        $this->silentSyndication = false;

        $this->principalSyndicationType = null;
        $this->principalParticipationType = null;
        $this->principalRiskType = null;

        $this->secondarySyndicationType = null;
        $this->secondaryParticipationType = null;
        $this->secondaryRiskType = null;

        $this->currentStatus = new ProjectStatus($this, $addedBy, ProjectStatus::DRAFT);
        $this->statuses      = new ArrayCollection([$this->currentStatus]);

        // This part is weird but compliant to figma models: those fields are editable
        $this->agentDisplayName = $this->agent->getDisplayName();
        $this->agentSiren       = $this->agent->getSiren();

        $this->agentBorrowerDrive = new Drive();
        $this->agentPrincipalParticipantDrive = new Drive();
        $this->agentSecondaryParticipantDrive = new Drive();
    }

    /**
     * @return Company
     */
    public function getAgent(): Company
    {
        return $this->agent;
    }

    /**
     * @param Company $agent
     *
     * @return Project
     */
    public function setAgent(Company $agent): Project
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAgentDisplayName(): ?string
    {
        return $this->agentDisplayName;
    }

    /**
     * @param string|null $agentDisplayName
     *
     * @return Project
     */
    public function setAgentDisplayName(?string $agentDisplayName): Project
    {
        $this->agentDisplayName = $agentDisplayName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAgentSiren(): ?string
    {
        return $this->agentSiren;
    }

    /**
     * @param string|null $agentSiren
     *
     * @return Project
     */
    public function setAgentSiren(?string $agentSiren): Project
    {
        $this->agentSiren = $agentSiren;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAgentLegalForm(): ?string
    {
        return $this->agentLegalForm;
    }

    /**
     * @param string|null $agentLegalForm
     *
     * @return Project
     */
    public function setAgentLegalForm(?string $agentLegalForm): Project
    {
        $this->agentLegalForm = $agentLegalForm;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    /**
     * @param string|null $headOffice
     *
     * @return Project
     */
    public function setHeadOffice(?string $headOffice): Project
    {
        $this->headOffice = $headOffice;

        return $this;
    }


    /**
     * @return NullableMoney|null
     */
    public function getAgentCapital(): ?NullableMoney
    {
        return $this->agentCapital;
    }

    /**
     * @param NullableMoney|null $agentCapital
     *
     * @return Project
     */
    public function setAgentCapital(?NullableMoney $agentCapital): Project
    {
        $this->agentCapital = $agentCapital;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAgentRCS(): ?string
    {
        return $this->agentRCS;
    }

    /**
     * @param string|null $agentRCS
     *
     * @return Project
     */
    public function setAgentRCS(?string $agentRCS): Project
    {
        $this->agentRCS = $agentRCS;

        return $this;
    }

    /**
     * @Groups({"agency:project:read"})
     *
     * @return Collection
     */
    public function getBackOfficeContacts(): Collection
    {
        return $this->getContactsByType(Contact::TYPE_BACK_OFFICE);
    }

    /**
     * @Groups({"agency:project:read"})
     *
     * @return Collection
     */
    public function getLegalContacts(): Collection
    {
        return $this->getContactsByType(Contact::TYPE_LEGAL);
    }

    /**
     * @return NullablePerson
     */
    public function getAgencyContact(): NullablePerson
    {
        return $this->agencyContact;
    }

    /**
     * @param NullablePerson $agencyContact
     *
     * @return Project
     */
    public function setAgencyContact(NullablePerson $agencyContact): Project
    {
        $this->agencyContact = $agencyContact;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBankInstitution(): ?string
    {
        return $this->bankInstitution;
    }

    /**
     * @param string|null $bankInstitution
     *
     * @return Project
     */
    public function setBankInstitution(?string $bankInstitution): Project
    {
        $this->bankInstitution = $bankInstitution;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @param string|null $bic
     *
     * @return Project
     */
    public function setBic(?string $bic): Project
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @param string|null $iban
     *
     * @return Project
     */
    public function setIban(?string $iban): Project
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * @param string $riskGroupName
     *
     * @return Project
     */
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

    /**
     * @return string|null
     */
    public function getInternalRatingScore(): ?string
    {
        return $this->internalRatingScore;
    }


    /**
     * @param string|null $internalRatingScore
     *
     * @return Project
     */
    public function setInternalRatingScore(?string $internalRatingScore): Project
    {
        $this->internalRatingScore = $internalRatingScore;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSilentSyndication(): bool
    {
        return $this->silentSyndication;
    }

    /**
     * @param bool $silentSyndication
     *
     * @return Project
     */
    public function setSilentSyndication(bool $silentSyndication): Project
    {
        $this->silentSyndication = $silentSyndication;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrincipalSyndicationType(): ?string
    {
        return $this->principalSyndicationType;
    }

    /**
     * @param string|null $principalSyndicationType
     *
     * @return Project
     */
    public function setPrincipalSyndicationType(?string $principalSyndicationType): Project
    {
        $this->principalSyndicationType = $principalSyndicationType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrincipalParticipationType(): ?string
    {
        return $this->principalParticipationType;
    }

    /**
     * @param string|null $principalParticipationType
     *
     * @return Project
     */
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

    /**
     * @return string|null
     */
    public function getPrincipalRiskType(): ?string
    {
        return $this->principalRiskType;
    }

    /**
     * @param string|null $principalRiskType
     *
     * @return Project
     */
    public function setPrincipalRiskType(?string $principalRiskType): Project
    {
        $this->principalRiskType = $principalRiskType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecondarySyndicationType(): ?string
    {
        return $this->secondarySyndicationType;
    }

    /**
     * @param string|null $secondarySyndicationType
     *
     * @return Project
     */
    public function setSecondarySyndicationType(?string $secondarySyndicationType): Project
    {
        $this->secondarySyndicationType = $secondarySyndicationType;

        return $this;
    }

    /**
     * @return string|null
     */
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

    /**
     * @param string|null $secondaryParticipationType
     *
     * @return Project
     */
    public function setSecondaryParticipationType(?string $secondaryParticipationType): Project
    {
        $this->secondaryParticipationType = $secondaryParticipationType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecondaryRiskType(): ?string
    {
        return $this->secondaryRiskType;
    }

    /**
     * @param string|null $secondaryRiskType
     *
     * @return Project
     */
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

    /**
     * @param string $title
     *
     * @return Project
     */
    public function setTitle(string $title): Project
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param Money $globalFundingMoney
     *
     * @return Project
     */
    public function setGlobalFundingMoney(Money $globalFundingMoney): Project
    {
        $this->globalFundingMoney = $globalFundingMoney;

        return $this;
    }

    /**
     * @return Money
     */
    public function getGlobalFundingMoney(): Money
    {
        return $this->globalFundingMoney;
    }

    /**
     * @return string|null
     */
    public function getFundingSpecificity(): ?string
    {
        return $this->fundingSpecificity;
    }

    /**
     * @param string|null $fundingSpecificity
     *
     * @return Project
     */
    public function setFundingSpecificity(?string $fundingSpecificity): Project
    {
        $this->fundingSpecificity = $fundingSpecificity;

        return $this;
    }

    /**
     * @return CompanyGroupTag|null
     */
    public function getCompanyGroupTag(): ?CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

    /**
     * @param CompanyGroupTag|null $companyGroupTag
     *
     * @return Project
     */
    public function setCompanyGroupTag(?CompanyGroupTag $companyGroupTag): Project
    {
        $this->companyGroupTag = $companyGroupTag;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getClosingDate(): DateTimeImmutable
    {
        return $this->closingDate;
    }

    /**
     * @param DateTimeImmutable $closingDate
     *
     * @return Project
     */
    public function setClosingDate(DateTimeImmutable $closingDate): Project
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getContractEndDate(): DateTimeImmutable
    {
        return $this->contractEndDate;
    }

    /**
     * @param DateTimeImmutable $contractEndDate
     *
     * @return Project
     */
    public function setContractEndDate(DateTimeImmutable $contractEndDate): Project
    {
        $this->contractEndDate = $contractEndDate;

        return $this;
    }

    /**
     * @param string|null $description
     *
     * @return Project
     */
    public function setDescription(?string $description): Project
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
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

    /**
     * @return ProjectStatus
     */
    public function getCurrentStatus(): ProjectStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param ProjectStatus $currentStatus
     *
     * @return Project
     */
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

    /**
     * @param Covenant $covenants
     *
     * @return Project
     */
    public function addCovenant(Covenant $covenants): Project
    {
        $this->covenants->add($covenants);

        return $this;
    }


    /**
     * @param Covenant $covenants
     *
     * @return Project
     */
    public function removeCovenant(Covenant $covenants): Project
    {
        $this->covenants->removeElement($covenants);

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return ProjectStatus::DRAFT > $this->getCurrentStatus()->getStatus();
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups
     *
     * @param Project $project
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(self $project): array
    {
        $validationGroups = ['Default', 'Project'];

        if ($project->isPublished()) {
            $validationGroups[] = ['published'];
        }

        return $validationGroups;
    }

    /**
     * @return Drive
     */
    public function getAgentBorrowerDrive(): Drive
    {
        return $this->agentBorrowerDrive;
    }

    /**
     * @return Drive
     */
    public function getAgentPrincipalParticipantDrive(): Drive
    {
        return $this->agentPrincipalParticipantDrive;
    }

    /**
     * @return Drive
     */
    public function getAgentSecondaryParticipantDrive(): Drive
    {
        return $this->agentSecondaryParticipantDrive;
    }

    /**
     * @param string $type
     *
     * @return Collection
     */
    private function getContactsByType(string $type): Collection
    {
        $filteredContacts = $this->contacts->filter(function (Contact $contact) use ($type) {
            return $contact->getType() === $type;
        });

        // necessary to reset array keys and return a Collection
        return new ArrayCollection($filteredContacts->getValues());
    }
}
