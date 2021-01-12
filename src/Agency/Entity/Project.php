<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Constant\LegalForm;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Entity\{Company, Constant\ParticipationType, Staff};

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "timestampable:read",
 *             "project:read"
 *         }
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "project:write"
 *         }
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"project:create"}}
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "normalization_context": {"groups": {"timestampable:read","project:read","contact:read"}},
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *     }
 * )
 *
 * @ORM\Table(name="agency_project")
 * @ORM\Entity
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Agency\Entity\Versioned\VersionedProject")
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
     * @Groups({"project:read", "project:create"})
     *
     * @Assert\NotBlank()
     */
    private Company $agent;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=300, nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $agentDisplayName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=9, nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     *
     * @Assert\Length(9)
     * @Assert\Luhn
     */
    private ?string $agentSiren;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     *
     * @Assert\Choice(callback={LegalForm::class, "getConstList"})
     */
    private ?string $agentLegalForm;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $headOffice;

    /**
     * @var NullableMoney|null
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?NullableMoney $agentCapital;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $agentRCS;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $agentRegistrationCity;

    /**
     * @var Contact[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Contact", mappedBy="project", orphanRemoval=true, cascade={"remove"})
     */
    private Collection $contacts;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $bankInstitution;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Bic
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $bic;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=34, nullable=true)
     *
     * @Assert\Iban
     *
     * @Groups({"project:read", "project:write"})
     */
    private ?string $iban;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private bool $silentSyndication;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Choice(callback={"Unilend\Core\Entity\Constant\SyndicationType", "getSyndicationTypes"})
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private ?string $principalSyndicationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Choice(callback={"Unilend\Core\Entity\Constant\ParticipationType", "getParticipationTypes"})
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private ?string $principalParticipationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Expression("(false === this.isPrincipalSubParticipation() and null === value) or (this.isPrincipalSubParticipation() and value)")
     *
     * @Assert\Choice(callback={"Unilend\Core\Entity\Constant\RiskType", "getRiskTypes"})
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private ?string $principalRiskType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={"Unilend\Core\Entity\Constant\SyndicationType", "getSyndicationTypes"})
     *
     * @Assert\Expression("(this.hasSilentSyndication() and value) or (false === this.hasSilentSyndication() and null === value)")
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private ?string $secondarySyndicationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={"Unilend\Core\Entity\Constant\ParticipationType", "getParticipationTypes"})
     *
     * @Assert\Expression("(this.hasSilentSyndication() and value) or (false === this.hasSilentSyndication() and null === value)")
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private ?string $secondaryParticipationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={"Unilend\Core\Entity\Constant\RiskType", "getRiskTypes"})
     *
     * @Assert\Expression("(false === this.isSecondarySubParticipation() and null === value) or (this.isSecondarySubParticipation() and value)"),
     * @Assert\Expression("(this.hasSilentSyndication()) or (false === this.hasSilentSyndication() and null === value)")
     *
     * @Groups({"project:write", "project:read", "project:create"})
     */
    private ?string $secondaryRiskType;

    /**
     * @var Borrower[]|iterable
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\Borrower", mappedBy="project")
     */
    private iterable $borrowers;

    /**
     * @param Staff $addedBy
     *
     * @throws Exception
     */
    public function __construct(Staff $addedBy)
    {
        $agent                   = $addedBy->getCompany();
        $this->added             = new DateTimeImmutable();
        $this->addedBy           = $addedBy;
        $this->contacts          = new ArrayCollection();
        $this->agent             = $agent;

        $this->borrowers = new ArrayCollection();

        $this->silentSyndication = false;

        $this->principalSyndicationType = null;
        $this->principalParticipationType = null;
        $this->principalRiskType = null;

        $this->secondarySyndicationType = null;
        $this->secondaryParticipationType = null;
        $this->secondaryRiskType = null;

        // This part is weird but compliant to figma models: those fields are editable
        $this->agentDisplayName = $agent->getDisplayName();
        $this->agentSiren       = $agent->getSiren();
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
     * @return string|null
     */
    public function getAgentRegistrationCity(): ?string
    {
        return $this->agentRegistrationCity;
    }

    /**
     * @param string|null $agentRegistrationCity
     *
     * @return Project
     */
    public function setAgentRegistrationCity(?string $agentRegistrationCity): Project
    {
        $this->agentRegistrationCity = $agentRegistrationCity;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getContactsByType(string $type): array
    {
        $filteredContacts = $this->contacts->filter(function (Contact $contact) use ($type) {
            return $contact->getType() === $type;
        });

        // necessary to reset array keys
        return array_values($filteredContacts->toArray());
    }

    /**
     * @Groups({"project:read"})
     *
     * @return array
     */
    public function getBackOfficeContacts(): array
    {
        return  $this->getContactsByType(Contact::TYPE_BACK_OFFICE);
    }

    /**
     * @Groups({"project:read"})
     *
     * @return array
     */
    public function getLegalContacts(): array
    {
        return $this->getContactsByType(Contact::TYPE_LEGAL);
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
}
