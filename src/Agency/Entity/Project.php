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
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Entity\{Company, Staff};

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

    public const LEGAL_FORM_EURL = 'EURL';
    public const LEGAL_FORM_SARL = 'SARL';
    public const LEGAL_FORM_SAS  = 'SAS';
    public const LEGAL_FORM_SASU = 'SASU';

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_agent", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"project:read"})
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
     * @Assert\Choice({Project::LEGAL_FORM_EURL, Project::LEGAL_FORM_SARL, Project::LEGAL_FORM_SAS,  Project::LEGAL_FORM_SASU})
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
     * @param Staff $addedBy
     *
     * @throws Exception
     */
    public function __construct(Staff $addedBy)
    {
        $agent          = $addedBy->getCompany();
        $this->added    = new DateTimeImmutable();
        $this->addedBy  = $addedBy;
        $this->contacts = new ArrayCollection();
        $this->agent    = $agent;

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
}
