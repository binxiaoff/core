<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource, ApiSubresource};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={"groups": {"company:read", "companyStatus:read", "staff:read", "client:read", "client_status:read", "role:read"}},
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get"
 *     }
 * )
 * @ApiFilter("Unilend\Filter\InvertedSearchFilter", properties={"projectParticipations.project.publicId", "projectParticipations.project"})
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Company implements TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;

    public const VAT_METROPOLITAN = 'metropolitan'; // Default tva category : 20 %
    public const VAT_OVERSEAS     = 'overseas'; // Overseas tva category (Guadeloupe, Martinique, Reunion) : 8.5 %

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", length=16777215)
     *
     * @Assert\NotBlank
     *
     * @Groups({"company:read", "company:jwt:read", "company:autocomplete:read"})
     */
    private string $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=15, nullable=true, unique=true)
     *
     * @Assert\Length(9)
     * @Assert\Luhn
     */
    private ?string $siren;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=12, nullable=true, unique=true)
     *
     * @Assert\Bic
     *
     * @Groups({"company:read"})
     */
    private ?string $bic;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Groups({"company:read"})
     */
    private ?string $groupName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=16, nullable=true, unique=true)
     *
     * @Groups({"company:read"})
     *
     * TODO If needed, it is possible to validate VAT numbers with https://github.com/ddeboer/vatin-bundle
     */
    private ?string $vatNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     *
     * @Groups({"company:read"})
     */
    private string $applicableVat;

    /**
     * @var Company|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_parent_company", referencedColumnName="id")
     * })
     *
     * @Groups({"company:read"})
     */
    private ?Company $parent;

    /**
     * @var Collection|Staff[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Staff", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     *
     * @ApiSubresource
     */
    private Collection $staff;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     *
     * @Groups({"company:read", "company:jwt:read"})
     */
    private ?string $emailDomain;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=4, nullable=true, unique=true)
     *
     * @Groups({"company:read", "company:jwt:read"})
     */
    private ?string $shortCode;

    /**
     * @var CompanyStatus|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\CompanyStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, nullable=true)
     *
     * @Groups({"company:read"})
     */
    private ?CompanyStatus $currentStatus;

    /**
     * @var Collection|CompanyStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\CompanyStatus", mappedBy="company", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     */
    private Collection $statuses;

    /**
     * @var Collection|CompanyModule[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\CompanyModule", mappedBy="company", indexBy="label")
     *
     * @ApiSubresource
     */
    private Collection $modules;

    /**
     * @param string $name
     *
     * @throws Exception
     */
    public function __construct(string $name)
    {
        $this->name          = $name;
        $this->staff         = new ArrayCollection();
        $this->statuses      = new ArrayCollection();
        $this->added         = new DateTimeImmutable();
        $this->modules       = new ArrayCollection();
        $this->applicableVat = static::VAT_METROPOLITAN;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Company
     */
    public function setName($name): Company
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @todo GuaranteeRequestGenerator won't work if the name has special characters
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $siren
     *
     * @return Company
     */
    public function setSiren(?string $siren): Company
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiren(): ?string
    {
        return $this->siren;
    }

    /**
     * Set idParentCompany.
     *
     * @param Company $parent
     *
     * @return Company
     */
    public function setParent(?Company $parent = null): Company
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get idParentCompany.
     *
     * @return Company|null
     */
    public function getParent(): ?Company
    {
        return $this->parent;
    }

    /**
     * @param Clients|null $client
     *
     * @return Collection|Staff[]
     */
    public function getStaff(?Clients $client = null): Collection
    {
        $criteria = new Criteria();

        if ($client) {
            $criteria->where(Criteria::expr()->eq('client', $client));
        }

        return $this->staff->matching($criteria);
    }

    /**
     * @param Staff $staff
     *
     * @return Company
     */
    public function removeStaff(Staff $staff): Company
    {
        $this->staff->removeElement($staff);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailDomain(): ?string
    {
        return $this->emailDomain;
    }

    /**
     * @param string|null $emailDomain
     *
     * @return Company
     */
    public function setEmailDomain(?string $emailDomain): Company
    {
        $this->emailDomain = $emailDomain;

        return $this;
    }

    /**
     * @return string
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * @param string $shortCode
     *
     * @return Company
     */
    public function setShortCode(string $shortCode): Company
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * @return CompanyStatus|null
     */
    public function getCurrentStatus(): ?CompanyStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param CompanyStatus|StatusInterface $currentStatus
     *
     * @return Company
     */
    public function setCurrentStatus(StatusInterface $currentStatus): Company
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProspect(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_PROSPECT === $currentStatus->getStatus();
    }

    /**
     * @return bool
     */
    public function hasSigned(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_SIGNED === $currentStatus->getStatus();
    }

    /**
     * @return bool
     */
    public function hasRefused(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_REFUSED === $currentStatus->getStatus();
    }

    /**
     * @param string $module
     *
     * @return bool
     */
    public function hasModuleActivated(string $module): bool
    {
        return isset($this->modules[$module]) && $this->modules[$module]->isActivated();
    }

    /**
     * @return Collection|CompanyModule[]
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * @return Collection|CompanyStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
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
     * @return Company
     */
    public function setBic(?string $bic): Company
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    /**
     * @param string|null $groupName
     *
     * @return Company
     */
    public function setGroupName(?string $groupName): Company
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    /**
     * @param string|null $vatNumber
     *
     * @return Company
     */
    public function setVatNumber(?string $vatNumber): Company
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getApplicableVat(): string
    {
        return $this->applicableVat;
    }

    /**
     * @param string $applicableVat
     *
     * @return Company
     */
    public function setApplicableVat(string $applicableVat): Company
    {
        $this->applicableVat = $applicableVat;

        return $this;
    }
}
