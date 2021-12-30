<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Embeddable\NullablePerson;
use KLS\Core\Entity\Interfaces\DriveCarrierInterface;
use KLS\Syndication\Agency\Entity\Embeddable\BankAccount;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:agent:read",
 *             "nullablePerson:read",
 *             "nullableMoney:read",
 *             "money:read",
 *             "agency:bankAccount:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     collectionOperations={},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "validation_groups": {Agent::class, "getCurrentValidationGroups"},
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:agent:write",
 *                     "nullablePerson:write",
 *                     "nullableMoney:write",
 *                     "money:write",
 *                     "agency:bankAccount:write",
 *                 },
 *                 "openapi_definition_name": "item-patch-write",
 *             },
 *         },
 *         "get_agent_dataroom": {
 *             "method": "GET",
 *             "path": "/agency/agent/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "controller": Get::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "confidentialDrive",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "item-get_agent_dataroom-read",
 *             },
 *         },
 *         "post_agent_dataroom": {
 *             "method": "POST",
 *             "path": "/agency/agent/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "deserialize": false,
 *             "controller": Post::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "confidentialDrive",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "item-post_agent_dataroom-read",
 *             },
 *         },
 *         "delete_agent_dataroom": {
 *             "method": "DELETE",
 *             "path": "/agency/agent/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "deserialize": false,
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "confidentialDrive",
 *             },
 *         },
 *     },
 * )
 * @ORM\Table(name="agency_agent")
 * @ORM\Entity
 */
class Agent extends AbstractProjectPartaker implements DriveCarrierInterface
{
    /**
     * @var Collection|AgentMember[]
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\Syndication\Agency\Entity\AgentMember", mappedBy="agent", cascade={"persist", "remove"}
     * )
     *
     * @Assert\Count(min=1)
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getAgent() === this")
     * })
     *
     * @Groups({"agency:agent:read"})
     */
    protected Collection $members;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Syndication\Agency\Entity\Project", inversedBy="agent")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE", unique=true)
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:agent:read"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Project $project;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     *
     * @Groups({"agency:agent:read"})
     *
     * @Assert\NotBlank
     */
    private Company $company;

    /**
     * @ORM\Column(type="string", length=300, nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private ?string $displayName;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullablePerson", columnPrefix="agency_contact_")
     *
     * @Assert\Valid
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private NullablePerson $contact;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\Drive", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_confidential_drive", nullable=false, unique=true, onDelete="CASCADE")
     */
    private Drive $confidentialDrive;

    /**
     * @var Collection|AgentBankAccount[]
     *
     * @ORM\OneToMany(targetEntity=AgentBankAccount::class, mappedBy="agent")
     *
     * @Assert\Valid
     *
     * @Groups({"agency:agent:read"})
     */
    private Collection $bankAccounts;

    public function __construct(Project $project, Company $company)
    {
        parent::__construct($company->getSiren() ?? '');
        $this->project           = $project;
        $this->company           = $company;
        $this->members           = new ArrayCollection();
        $this->displayName       = $company->getDisplayName();
        $this->corporateName     = $company->getDisplayName();
        $this->confidentialDrive = new Drive();
        $this->bankAccounts      = new ArrayCollection();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): Agent
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getContact(): NullablePerson
    {
        return $this->contact;
    }

    public function setContact(NullablePerson $contact): Agent
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection|AgentBankAccount[]
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
    }

    public function addBankAccount(AgentBankAccount $agentBankAccount): Agent
    {
        if (false === $this->bankAccounts->contains($agentBankAccount)) {
            $this->bankAccounts->add($agentBankAccount);
        }

        return $this;
    }

    public function removeBankAccount(AgentBankAccount $agentBankAccount): Agent
    {
        if ($this->bankAccounts->contains($agentBankAccount)) {
            $this->bankAccounts->remove($agentBankAccount);
        }

        return $this;
    }

    /**
     * @param Collection|AgentBankAccount[] $bankAccounts
     *
     * @return Agent
     */
    public function setBankAccounts($bankAccounts)
    {
        $this->bankAccounts = $bankAccounts;

        return $this;
    }

    /**
     * @Groups({"agency:agent:read"})
     */
    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setMatriculationNumber(string $matriculationNumber): AbstractProjectPartaker
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    /**
     * @Groups({"agency:agent:read"})
     */
    public function getCapital(): NullableMoney
    {
        return parent::getCapital();
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setCapital(NullableMoney $capital): AbstractProjectPartaker
    {
        return parent::setCapital($capital);
    }

    /**
     * @Groups({"agency:agent:read"})
     */
    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setRcs(?string $rcs): AbstractProjectPartaker
    {
        $this->rcs = $rcs;

        return $this;
    }

    /**
     * @Groups({"agency:agent:read"})
     *
     * @Assert\NotBlank(groups={"published"})
     */
    public function getCorporateName(): ?string
    {
        return $this->corporateName;
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setCorporateName(?string $corporateName): AbstractProjectPartaker
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    /**
     * @Groups({"agency:agent:read"})
     *
     * @Assert\NotBlank(groups={"published"})
     */
    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setHeadOffice(?string $headOffice): AbstractProjectPartaker
    {
        $this->headOffice = $headOffice;

        return $this;
    }

    /**
     * @Groups({"agency:agent:read"})
     *
     * @Assert\NotBlank(groups={"published"})
     */
    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setLegalForm(?string $legalForm): Agent
    {
        return parent::setLegalForm($legalForm);
    }

    public function getConfidentialDrive(): Drive
    {
        return $this->confidentialDrive;
    }

    /**
     * @Groups({"agency:agent:read"})
     */
    public function hasVariableCapital(): ?bool
    {
        return parent::hasVariableCapital();
    }

    /**
     * @Groups({"agency:agent:write"})
     */
    public function setVariableCapital(?bool $variableCapital): AbstractProjectPartaker
    {
        return parent::setVariableCapital($variableCapital);
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups.
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(AbstractProjectPartaker $abstractProjectPartaker): array
    {
        $validationGroups = parent::getCurrentValidationGroups($abstractProjectPartaker);

        $validationGroups[] = 'Agent';

        if ($abstractProjectPartaker->getProject()->isPublished()) {
            $validationGroups[] = 'bankAccount::completed';
        }

        return $validationGroups;
    }
}
