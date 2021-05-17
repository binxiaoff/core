<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Embeddable\NullablePerson;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\User;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:agent:read",
 *             "agency:projectPartaker:read",
 *             "nullableMoney:read"
 *         }
 *     },
 *     collectionOperations={},
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {"agency:agent:write", "agency:projectPartaker:write"}
 *             }
 *         }
 *     }
 * )
 * @ORM\Table(name="agency_agent")
 * @ORM\Entity
 */
class Agent extends AbstractProjectPartaker
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="agent")
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
     * @var Collection|AgentMember[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\AgentMember", mappedBy="agent", cascade={"persist", "remove"})
     *
     * @Assert\Count(min=1)
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getAgent() == this")
     * })
     *
     * @Groups({"agency:agent:read"})
     */
    private Collection $members;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
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
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private ?string $legalForm;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private ?string $headOffice;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private ?string $bankInstitution;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Bic
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private ?string $bic;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     *
     * @Assert\Iban
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private ?string $iban;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullablePerson", columnPrefix="agency_contact_")
     *
     * @Assert\Valid
     *
     * @Groups({"agency:agent:read", "agency:agent:write"})
     */
    private NullablePerson $contact;

    public function __construct(Project $project, Company $company)
    {
        parent::__construct($company->getSiren() ?? '', new Money($project->getCurrency(), '0'));
        $this->project     = $project;
        $this->company     = $company;
        $this->members     = new ArrayCollection();
        $this->displayName = $company->getDisplayName();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getMembers()
    {
        return $this->members;
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

    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    public function setLegalForm(?string $legalForm): Agent
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    public function setHeadOffice(?string $headOffice): Agent
    {
        $this->headOffice = $headOffice;

        return $this;
    }

    public function getBankInstitution(): ?string
    {
        return $this->bankInstitution;
    }

    public function setBankInstitution(?string $bankInstitution): Agent
    {
        $this->bankInstitution = $bankInstitution;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): Agent
    {
        $this->bic = $bic;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): Agent
    {
        $this->iban = $iban;

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

    public function addMember(AgentMember $member): Agent
    {
        if (null === $this->findMemberByUser($member->getUser())) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function findMemberByUser(User $user): ?AgentMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }

        return null;
    }
}
