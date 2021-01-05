<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

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
     * @Assert\NotBlank()
     *
     * @Groups({"project:read", "project:create"})
     */
    private Company $agent;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=300, nullable=true)
     *
     * @Groups({"project:read", "project:write"})
     *
     * @Assert\NotBlank
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
     * @param Company $agent
     *
     * @throws Exception
     */
    public function __construct(Company $agent)
    {
        $this->added = new DateTimeImmutable();
        $this->agent = $agent;

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
}
