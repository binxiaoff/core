<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * Companies.
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
     * @ORM\Column(name="forme", type="string", length=191, nullable=true)
     *
     * @Assert\NotBlank
     */
    private $forme;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_form_code", type="string", length=10, nullable=true)
     */
    private $legalFormCode;

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
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="date", nullable=true)
     */
    private $dateCreation;

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
     * @var ProjectParticipant[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipant", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipants;

    /**
     * Companies constructor.
     */
    public function __construct()
    {
        $this->staff               = new ArrayCollection();
        $this->projectParticipants = new ArrayCollection();
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
     * @param Clients|null $client
     *
     * @return Companies
     *
     * @deprecated use $this->addStaff() instead
     *
     * Set idClientOwner
     */
    public function setIdClientOwner(Clients $client = null): Companies
    {
        if ($client) {
            $this->addStaff($client, Staff::ROLE_COMPANY_OWNER);
        }

        return $this;
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
     * TODO GuaranteeRequestGenerator won't work if the name has special characters
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set forme.
     *
     * @param string $forme
     *
     * @return Companies
     */
    public function setForme($forme): Companies
    {
        $this->forme = $forme;

        return $this;
    }

    /**
     * Get forme.
     *
     * @return string
     */
    public function getForme(): string
    {
        return $this->forme;
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
     * @return string
     */
    public function getSiret(): string
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
     * @return Companies
     */
    public function getParent(): Companies
    {
        return $this->parent;
    }

    /**
     * @return string|null
     */
    public function getLegalFormCode(): ?string
    {
        return $this->legalFormCode;
    }

    /**
     * @param string $legalFormCode
     */
    public function setLegalFormCode($legalFormCode = null): void
    {
        $this->legalFormCode = $legalFormCode;
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
     * @return ArrayCollection|ProjectParticipant[]
     */
    public function getProjectParticipants(?Project $project = null): iterable
    {
        $criteria = new Criteria();
        if ($project) {
            $criteria->where(Criteria::expr()->eq('project', $project));
        }

        return $this->projectParticipants->matching($criteria);
    }

    /**
     * @param Project $project
     *
     * @return bool
     */
    public function isArranger(Project $project): bool
    {
        $projectParticipant = $this->getProjectParticipants($project)->first();
        if ($projectParticipant instanceof ProjectParticipant) {
            return $projectParticipant->isArranger();
        }

        return false;
    }
}
