<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\CompaniesRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Companies
{
    use TimestampableTrait;

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
     * @var CompanyStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyStatus")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_status", referencedColumnName="id")
     * })
     */
    private $idStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", length=16777215, nullable=true)
     *
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="siren", type="string", length=15, nullable=true)
     *
     * @Assert\NotBlank
     * @Assert\Length(min=9, max=14)
     */
    private $siren;

    /**
     * @var string
     *
     * @ORM\Column(name="siret", type="string", length=14, nullable=true)
     */
    private $siret;

    /**
     * @var int
     *
     * @ORM\Column(name="id_company", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompany;

    /**
     * @var Companies|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_parent_company", referencedColumnName="id_company")
     * })
     */
    private $parent;

    /**
     * @var Staff[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Staff", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    private $staff;

    /**
     * @var ProjectParticipation[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $emailDomain;

    /**
     * Companies constructor.
     */
    public function __construct()
    {
        $this->staff                 = new ArrayCollection();
        $this->projectParticipations = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getIdCompany();
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
     * Set siren.
     *
     * @param string $siren
     *
     * @return Companies
     */
    public function setSiren($siren): Companies
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * Get siren.
     *
     * @return string
     */
    public function getSiren(): string
    {
        return $this->siren;
    }

    /**
     * Set siret.
     *
     * @param string $siret
     *
     * @return Companies
     */
    public function setSiret($siret): Companies
    {
        $this->siret = $siret;

        return $this;
    }

    /**
     * Get siret.
     *
     * @return string|null
     */
    public function getSiret(): ?string
    {
        return $this->siret;
    }

    /**
     * Get idCompany.
     *
     * @return int
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set idParentCompany.
     *
     * @param Companies $parent
     *
     * @return Companies
     */
    public function setParent(Companies $parent = null): Companies
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
     * @return CompanyStatus|null
     */
    public function getIdStatus(): ?CompanyStatus
    {
        return $this->idStatus;
    }

    /**
     * @param CompanyStatus $idStatus
     *
     * @return Companies
     */
    public function setIdStatus(CompanyStatus $idStatus): Companies
    {
        $this->idStatus = $idStatus;

        return $this;
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
     * @return Staff
     */
    public function addStaff(Clients $client, string $role): Staff
    {
        $staff = $this->getStaff($client);

        if ($staff->count()) {
            $theStaff = $staff->first();
        } else {
            $theStaff = (new Staff())->setClient($client)->setCompany($this);
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
     * @param Project $project
     *
     * @return bool
     */
    public function isArranger(Project $project): bool
    {
        $projectParticipation = $this->getProjectParticipations($project)->first();
        if ($projectParticipation instanceof ProjectParticipation) {
            return $projectParticipation->isArranger();
        }

        return false;
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
}
