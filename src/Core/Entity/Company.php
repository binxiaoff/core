<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     attributes={
 *         "pagination_enabled": false
 *     },
 *     collectionOperations={
 *         "get": {
 *         "normalization_context": {
 *              "groups": {
 *                  "company:read",
 *                  "companyStatus:read",
 *                  "companyModule:read",
 *                  "companyGroupTag:read",
 *                  "staff:read",
 *                  "user:read",
 *                  "user_status:read",
 *                  "nullableMoney:read"
 *              }
 *          }
 *        }
 *     },
 *     itemOperations={
 *         "get": {
 *              "normalization_context": {
 *                  "groups": {
 *                  "company:read",
 *                  "companyStatus:read",
 *                  "companyModule:read",
 *                  "staff:read",
 *                  "user:read",
 *                  "user_status:read",
 *                  "nullableMoney:read",
 *                  "team:read"
 *              }
 *          },
 *        }
 *     },
 *     itemOperations={
 *         "get": {
 *           "normalization_context": {
 *              "groups": {
 *                  "company:read",
 *                  "companyStatus:read",
 *                  "companyModule:read",
 *                  "companyGroupTag:read",
 *                  "staff:read",
 *                  "user:read",
 *                  "user_status:read",
 *                  "nullableMoney:read",
 *                  "team:read"
 *              }
 *          },
 *        },
 *         "staff": {
 *              "method": "GET",
 *              "path": "/core/companies/{id}/staff",
 *              "controller": "\Unilend\Core\Controller\Company\Staff",
 *              "normalization_context": {
 *                  "groups": {
 *                      "staff:read",
 *                      "user:read",
 *                      "user_status:read",
 *                      "nullableMoney:read",
 *                  }
 *              }
 *         },
 *         "companyGroupTags": {
 *              "method": "GET",
 *              "path": "/companies/{id}/company_group_tags",
 *              "controller": "\Unilend\Core\Controller\Company\CompanyGroupTag",
 *              "normalization_context": {
 *                  "groups": {
 *                      "companyGroupTag:read"
 *                  }
 *              }
 *         },
 *         "teams": {
 *              "method": "GET",
 *              "path": "/core/companies/{id}/teams",
 *              "controller": "\Unilend\Core\Controller\Company\Team",
 *              "normalization_context": {
 *                  "groups": {
 *                      "team:read"
 *                  }
 *              }
 *         }
 *     }
 * )
 * @ApiFilter("Unilend\Core\Filter\InvertedSearchFilter", properties={"projectParticipations.project.publicId", "projectParticipations.project", "groupName"})
 * @ApiFilter(SearchFilter::class, properties={"groupName"})
 * @ApiFilter("Unilend\Core\Filter\Company\ParticipantCandidateFilter")
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="core_company")
 */
class Company implements TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;

    public const VAT_METROPOLITAN = 'metropolitan'; // Default tva category : 20 %
    public const VAT_OVERSEAS     = 'overseas'; // Overseas tva category (Guadeloupe, Martinique, Reunion) : 8.5 %

    public const COMPANY_NAME_CALS = 'CA Lending Services';

    public const SHORT_CODE_CASA = 'CASA';

    public const NON_ELIGIBLE_TO_PARTICIPANT = ['CASA'];

    public const SERIALIZER_GROUP_COMPANY_STAFF_READ      = 'company:staff:read';
    public const SERIALIZER_GROUP_COMPANY_ADMIN_READ      = 'company:admin:read';
    public const SERIALIZER_GROUP_COMPANY_ACCOUNTANT_READ = 'company:accountant:read';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=300)
     *
     * @Assert\NotBlank
     *
     * @Groups({"company:read", "company:jwt:read"})
     */
    private string $displayName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=300)
     *
     * @Assert\NotBlank
     *
     * @Groups({"company:read", "company:jwt:read"})
     */
    private string $companyName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=9, nullable=true, unique=true)
     *
     * @Assert\Length(9)
     * @Assert\Luhn
     */
    private ?string $siren;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10, unique=true)
     *
     * @Assert\NotBlank
     *
     * @Groups({"company:read"})
     */
    private string $bankCode;

    /**
     * @var CompanyGroup|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\CompanyGroup")
     * @ORM\JoinColumn(name="id_company_group")
     */
    private ?CompanyGroup $companyGroup;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=16, nullable=true, unique=true)
     *
     * @Groups({"company:read"})
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
     * @var Team
     *
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\Team", cascade={"persist"}, inversedBy="company")
     * @ORM\JoinColumn(name="id_root_team", nullable=false, unique=true)
     */
    private Team $rootTeam;

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
     * @ORM\Column(type="string", length=10, nullable=false, unique=true)
     *
     * @Groups({"company:read", "company:jwt:read"})
     */
    private string $shortCode;

    /**
     * @var CompanyStatus|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\CompanyStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, nullable=true)
     *
     * @Groups({"company:read"})
     */
    private ?CompanyStatus $currentStatus;

    /**
     * @var Collection|CompanyStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\CompanyStatus", mappedBy="company", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     */
    private Collection $statuses;

    /**
     * @var Collection|CompanyModule[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\CompanyModule", mappedBy="company", indexBy="code", cascade={"persist"})
     *
     * @Groups({Company::SERIALIZER_GROUP_COMPANY_ADMIN_READ, Company::SERIALIZER_GROUP_COMPANY_ACCOUNTANT_READ})
     */
    private Collection $modules;

    /**
     * @var iterable|CompanyAdmin[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\CompanyAdmin", mappedBy="company")
     */
    private iterable $admins;

    /**
     * @param string $displayName
     * @param string $companyName
     *
     * @throws Exception
     */
    public function __construct(string $displayName, string $companyName)
    {
        $this->displayName  = $displayName;
        $this->companyName  = $companyName;
        $this->rootTeam     = Team::createRootTeam($this);
        $this->statuses     = new ArrayCollection();
        $this->added        = new DateTimeImmutable();
        $this->admins       = new ArrayCollection();
        $this->companyGroup = null;
        $moduleCodes         = CompanyModule::getAvailableModuleCodes();
        $this->modules       = new ArrayCollection(array_map(function ($module) {
            return new CompanyModule($module, $this);
        }, array_combine($moduleCodes, $moduleCodes)));
        $this->applicableVat = static::VAT_METROPOLITAN;
        $this->setCurrentStatus(new CompanyStatus($this, CompanyStatus::STATUS_PROSPECT));
    }

    /**
     * @todo GuaranteeRequestGenerator won't work if the name has special characters
     * Get name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Set name.
     *
     * @param string $displayName
     *
     * @return Company
     */
    public function setDisplayName(string $displayName): Company
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @return string|null
     */
    public function getSiren(): ?string
    {
        return $this->siren;
    }

    /**
     * @param string|null $siren
     *
     * @return Company
     */
    public function setSiren(?string $siren): Company
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return Staff[]|iterable
     */
    public function getStaff(): iterable
    {
        foreach ($this->getTeams() as $team) {
            yield from $team->getStaff();
        }
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
    public function getShortCode(): string
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
     * @return bool
     */
    public function isProspect(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_PROSPECT === $currentStatus->getStatus();
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function isProspectAt(DateTimeInterface $dateTime): bool
    {
        $status = $this->getCurrentStatusAt($dateTime);

        return $status && CompanyStatus::STATUS_PROSPECT === $status->getStatus();
    }

    /**
     * @param Company $company
     *
     * @return bool
     */
    public function isSameGroup(Company $company): bool
    {
        return $this->getGroupName() === $company->getGroupName();
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
     * @return CompanyModule
     */
    public function getModule(string $module): CompanyModule
    {
        return $this->modules[$module];
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
     * @return array
     *
     * @Groups({Company::SERIALIZER_GROUP_COMPANY_STAFF_READ})
     */
    public function getActivatedModules(): array
    {
        $activatedModules = $this->modules->filter(static function (CompanyModule $module) {
            return $module->isActivated();
        });

        $activatedModuleCodes = $activatedModules->map(static function (CompanyModule $module) {
            return $module->getCode();
        });

        return array_values($activatedModuleCodes->toArray());
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
     * @return string
     */
    public function getBankCode(): string
    {
        return $this->bankCode;
    }

    /**
     * @param string $bankCode
     *
     * @return Company
     */
    public function setBankCode(string $bankCode): Company
    {
        $this->bankCode = $bankCode;

        return $this;
    }

    /**
     * @return string|null
     *
     * @Groups({"company:read"})
     */
    public function getGroupName(): ?string
    {
        return $this->companyGroup ? $this->companyGroup->getName() : null;
    }

    /**
     * @return CompanyGroup|null
     */
    public function getCompanyGroup(): ?CompanyGroup
    {
        return $this->companyGroup;
    }

    /**
     * @param CompanyGroup|null $companyGroup
     *
     * @return Company
     */
    public function setCompanyGroup(?CompanyGroup $companyGroup): Company
    {
        $this->companyGroup = $companyGroup;

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

    /**
     * CAG means Crédit Agricole Group
     *
     * @return bool
     */
    public function isCAGMember(): bool
    {
        return $this->getGroupName() === CompanyGroup::GROUPNAME_CA;
    }

    /**
     * @return array
     */
    public static function getPossibleVatTypes(): array
    {
        return self::getConstants('VAT_');
    }

    /**
     * @return iterable|CompanyAdmin[]
     */
    public function getAdmins(): iterable
    {
        return $this->admins;
    }

    /**
     * @return Team
     */
    public function getRootTeam(): Team
    {
        return $this->rootTeam;
    }

    /**
     * @return Team[]|iterable
     */
    public function getTeams(): iterable
    {
        yield $this->rootTeam;

        yield from $this->rootTeam->getDescendents();
    }

    /**
     * @return CompanyGroupTag[]|iterable
     */
    public function getCompanyGroupTags(): iterable
    {
        return $this->companyGroup ? $this->companyGroup->getTags() : [];
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return CompanyStatus|null
     */
    private function getCurrentStatusAt(DateTimeInterface $dateTime): ?CompanyStatus
    {
        /** @var CompanyStatus $status */
        $previousStatuses = $this->getStatuses()->filter(static function ($status) use ($dateTime) {
            return $status->getAdded() <= $dateTime;
        });

        return $previousStatuses ? $previousStatuses->last() : null;
    }
}
