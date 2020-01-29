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
use Unilend\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait, TraceableStatusTrait};

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={"groups": {"company:read", "staff:read", "profile:read", "client_status:read", "role:read"}},
 *     collectionOperations={
 *         "get",
 *         "autocomplete": {
 *             "method": "get",
 *             "path": "/companies/autocomplete/{term}",
 *             "controller": "Unilend\Controller\Companies\Autocomplete"
 *         }
 *     },
 *     itemOperations={
 *         "get"
 *     }
 * )
 * @ApiFilter("Unilend\Filter\InvertedSearchFilter", properties={"projectParticipations.project.hash", "projectParticipations.project"})
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\CompaniesRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @method CompanyStatus|null getCurrentStatus()
 */
class Companies
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;
    use TraceableStatusTrait {
        setCurrentStatus as baseStatusSetter;
    }

    public const INVALID_SIREN_EMPTY = '000000000';

    public const COMPANY_ID_CASA      = 1;
    public const COMPANY_ID_CACIB     = 2;
    public const COMPANY_ID_UNIFERGIE = 3;
    public const COMPANY_ID_LCL       = 43;

    public const COMPANY_ELIGIBLE_ARRANGER       = [self::COMPANY_ID_CACIB, self::COMPANY_ID_UNIFERGIE];
    public const COMPANY_ELIGIBLE_RUN            = [self::COMPANY_ID_LCL];
    public const COMPANY_SUBSIDIARY_ELIGIBLE_RUN = [self::COMPANY_ID_CASA];

    public const TRANSLATION_CREATION_IN_PROGRESS = 'creation-in-progress';

    /**
     * TODO Remove project:update group when autocomplete is done.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="text", length=16777215)
     *
     * @Assert\NotBlank
     *
     * @Groups({"project:create", "project:list", "project:update", "project:view", "company:read", "company:jwt:read"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="siren", type="string", length=15, nullable=true)
     *
     * @Assert\Length(9)
     * @Assert\Luhn
     *
     * @Groups({"project:create", "project:view"})
     */
    private $siren;

    /**
     * @var Companies|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_parent_company", referencedColumnName="id")
     * })
     *
     * @Groups({"company:read"})
     */
    private $parent;

    /**
     * @var Staff[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Staff", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     *
     * @ApiSubresource
     */
    private $staff;

    /**
     * TODO Is it really necessary ? (I am talking about the reverse association).
     *
     * @var ProjectParticipation[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     *
     * @Groups({"company:read", "company:jwt:read", "project:view"})
     */
    private $emailDomain;

    /**
     * @ORM\Column(type="string", length=4, nullable=true, unique=true)
     *
     * @Groups({"project:list", "project:view", "company:read", "company:jwt:read"})
     */
    private $shortCode;

    /**
     * @var CompanyStatus
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\CompanyStatus")
     * @ORM\JoinColumn(name="id_current_status", unique=true, nullable=true)
     */
    private $currentStatus;

    /**
     * @var ArrayCollection|CompanyStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\CompanyStatus", mappedBy="company")
     * @ORM\OrderBy({"added": "ASC"})
     */
    private $statuses;

    /**
     * @param string $name
     *
     * @throws Exception
     */
    public function __construct(string $name)
    {
        $this->name                  = $name;
        $this->staff                 = new ArrayCollection();
        $this->projectParticipations = new ArrayCollection();
        $this->statuses              = new ArrayCollection();
        $this->added                 = new DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return Clients|null
     *
     * @deprecated use $this->getStaff() instead
     *
     * Get idClientOwner
     */
    public function getIdClientOwner(): ?Clients
    {
        foreach ($this->getStaff() as $staff) {
            if ($staff->hasRole(Staff::ROLE_COMPANY_OWNER)) {
                return $staff->getClient();
            }
        }

        return null;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Companies
     */
    public function setName($name): Companies
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
     * @return Companies
     */
    public function setSiren(?string $siren): Companies
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
     * @param Companies $parent
     *
     * @return Companies
     */
    public function setParent(?Companies $parent = null): Companies
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get idParentCompany.
     *
     * @return Companies|null
     */
    public function getParent(): ?Companies
    {
        return $this->parent;
    }

    /**
     * @param Clients|null $client
     *
     * @return Staff[]|Collection
     */
    public function getStaff(?Clients $client = null): iterable
    {
        $criteria = new Criteria();

        if ($client) {
            $criteria->where(Criteria::expr()->eq('client', $client));
        }

        return $this->staff->matching($criteria);
    }

    /**
     * @param Clients $client
     * @param string  $role
     *
     * @throws Exception
     *
     * @return Staff
     */
    public function addStaff(Clients $client, string $role): Staff
    {
        $staff = $this->getStaff($client);

        if ($staff->count()) {
            $theStaff = $staff->first();
        } else {
            $theStaff = (new Staff($this, $client));
        }

        $theStaff->addRoles([$role]);
        $this->staff->add($theStaff);

        return $theStaff;
    }

    /**
     * @param Staff $staff
     *
     * @return Companies
     */
    public function removeStaff(Staff $staff): Companies
    {
        $this->staff->removeElement($staff);

        return $this;
    }

    /**
     * @param Project|null $project
     *
     * @return ArrayCollection|ProjectParticipation[]
     */
    public function getProjectParticipations(?Project $project = null): iterable
    {
        $criteria = new Criteria();
        if ($project) {
            $criteria->where(Criteria::expr()->eq('project', $project));
        }

        return $this->projectParticipations->matching($criteria);
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
     * @return Companies
     */
    public function setEmailDomain(?string $emailDomain): Companies
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
     * @return Companies
     */
    public function setShortCode(string $shortCode): Companies
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * @param int $status
     *
     * @throws Exception
     *
     * @return Companies
     */
    public function setCurrentStatus(int $status): self
    {
        return $this->baseStatusSetter(new CompanyStatus($this, $status));
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
}
