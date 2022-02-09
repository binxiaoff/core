<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a bank that might be one of our client.
 * In syndication, they are called the lender banks.
 * A company only knows its root team.
 *
 * @ApiResource(
 *     attributes={
 *         "pagination_enabled": false
 *     },
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "company:read",
 *                     "companyStatus:read",
 *                     "companyModule:read",
 *                     "companyGroupTag:read",
 *                     "staff:read",
 *                     "user:read",
 *                     "user_status:read",
 *                     "nullableMoney:read",
 *                 },
 *                 "openapi_definition_name": "collection-get-read",
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "company:read",
 *                     "companyStatus:read",
 *                     "companyModule:read",
 *                     "companyGroupTag:read",
 *                     "staff:read",
 *                     "user:read",
 *                     "user_status:read",
 *                     "nullableMoney:read",
 *                     "team:read",
 *                 },
 *                 "openapi_definition_name": "item-get-read",
 *             },
 *         },
 *         "staff": {
 *             "method": "GET",
 *             "path": "/core/companies/{publicId}/staff",
 *             "controller": "\KLS\Core\Controller\Company\Staff",
 *             "normalization_context": {
 *                 "groups": {
 *                     "staff:read",
 *                     "user:read",
 *                     "user_status:read",
 *                     "staffStatus:read",
 *                 },
 *                 "openapi_definition_name": "item-staff-read",
 *             },
 *         },
 *         "company_group_tags": {
 *             "method": "GET",
 *             "path": "/core/companies/{publicId}/company_group_tags",
 *             "controller": "\KLS\Core\Controller\Company\CompanyGroupTag",
 *             "normalization_context": {
 *                 "groups": {
 *                     "companyGroupTag:read",
 *                 },
 *                 "openapi_definition_name": "item-company_group_tags-read",
 *             },
 *         },
 *         "teams": {
 *             "method": "GET",
 *             "path": "/core/companies/{publicId}/teams",
 *             "controller": "\KLS\Core\Controller\Company\Team",
 *             "normalization_context": {
 *                 "groups": {
 *                     "team:read",
 *                 },
 *                 "openapi_definition_name": "item-teams-read",
 *             },
 *         },
 *     },
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"groupName"})
 * @ApiFilter(
 *     "KLS\Core\Filter\InvertedSearchFilter",
 *     properties={"projectParticipations.project.publicId", "projectParticipations.project", "groupName"}
 * )
 * @ApiFilter("KLS\Core\Filter\Company\ParticipantCandidateFilter")
 * @ApiFilter("KLS\Core\Filter\Company\CARegionalBankFilter")
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

    public const SHORT_CODE_KLS  = 'KLS';
    public const SHORT_CODE_CASA = 'CASA';

    public const NON_ELIGIBLE_TO_PARTICIPANT = [self::SHORT_CODE_CASA];

    public const SERIALIZER_GROUP_COMPANY_STAFF_READ      = 'company:staff:read';
    public const SERIALIZER_GROUP_COMPANY_ADMIN_READ      = 'company:admin:read';
    public const SERIALIZER_GROUP_COMPANY_ACCOUNTANT_READ = 'company:accountant:read';

    /**
     * @ORM\Column(type="string", length=300)
     *
     * @Assert\NotBlank
     *
     * @Groups({"company:read"})
     */
    private string $displayName;

    /**
     * @ORM\Column(type="string", length=300, nullable=true)
     *
     * @Groups({"company:read"})
     */
    private ?string $legalName = null;

    /**
     * @ORM\Column(type="string", length=9, unique=true)
     *
     * @Assert\Length(9)
     * @Assert\Luhn
     */
    private string $siren;

    /**
     * @ORM\Column(type="string", length=10, nullable=true, unique=true)
     *
     * @Groups({"company:read"})
     */
    private ?string $clientNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\CompanyGroup")
     * @ORM\JoinColumn(name="id_company_group")
     */
    private ?CompanyGroup $companyGroup = null;

    /**
     * @ORM\Column(type="string", length=16, nullable=true, unique=true)
     *
     * @Groups({"company:read"})
     */
    private ?string $vatNumber = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     *
     * @Groups({"company:read"})
     */
    private ?string $applicableVat = null;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\Team", cascade={"persist"}, inversedBy="company")
     * @ORM\JoinColumn(name="id_root_team", nullable=false, unique=true)
     */
    private Team $rootTeam;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     *
     * @Groups({"company:read"})
     */
    private ?string $emailDomain = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=10, nullable=false, unique=true)
     *
     * @Groups({"company:read"})
     */
    private string $shortCode;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\CompanyStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, nullable=true)
     *
     * @Groups({"company:read"})
     */
    private ?CompanyStatus $currentStatus;

    /**
     * @var Collection|CompanyStatus[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\CompanyStatus", mappedBy="company", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     */
    private Collection $statuses;

    /**
     * @var Collection|CompanyModule[]
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\Core\Entity\CompanyModule",
     *     mappedBy="company",
     *     indexBy="code",
     *     cascade={"persist"}
     * )
     *
     * @Groups({Company::SERIALIZER_GROUP_COMPANY_ADMIN_READ, Company::SERIALIZER_GROUP_COMPANY_ACCOUNTANT_READ})
     */
    private Collection $modules;

    /**
     * @var iterable|CompanyAdmin[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\CompanyAdmin", mappedBy="company")
     */
    private iterable $admins;

    /**
     * @throws Exception
     */
    public function __construct(string $displayName, string $siren)
    {
        $this->displayName = $displayName;
        $this->rootTeam    = Team::createRootTeam($this);
        $this->statuses    = new ArrayCollection();
        $this->added       = new DateTimeImmutable();
        $this->admins      = new ArrayCollection();
        $this->siren       = $siren;
        $moduleCodes       = CompanyModule::getAvailableModuleCodes();
        $this->modules     = new ArrayCollection(\array_map(function ($module) {
            return new CompanyModule($module, $this);
        }, \array_combine($moduleCodes, $moduleCodes)));
        $this->setCurrentStatus(new CompanyStatus($this, CompanyStatus::STATUS_PROSPECT));
    }

    /**
     * @todo GuaranteeRequestGenerator won't work if the name has special characters
     * Get name.
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Set name.
     */
    public function setDisplayName(string $displayName): Company
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): Company
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getSiren(): string
    {
        return $this->siren;
    }

    public function setSiren(string $siren): Company
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
     * @Groups({"company:read"})
     */
    public function getStaffCount(): int
    {
        return \iterator_count($this->getStaff());
    }

    public function findStaffByUser(User $user): ?Staff
    {
        foreach ($this->getStaff() as $staff) {
            if ($staff->getUser() === $user) {
                return $staff;
            }
        }

        return null;
    }

    public function getEmailDomain(): ?string
    {
        return $this->emailDomain;
    }

    public function setEmailDomain(?string $emailDomain): Company
    {
        $this->emailDomain = $emailDomain;

        return $this;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function setShortCode(string $shortCode): Company
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    public function isProspect(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_PROSPECT === $currentStatus->getStatus();
    }

    public function isProspectAt(DateTimeInterface $dateTime): bool
    {
        $status = $this->getCurrentStatusAt($dateTime);

        return $status && CompanyStatus::STATUS_PROSPECT === $status->getStatus();
    }

    public function isSameGroup(Company $company): bool
    {
        return $this->getCompanyGroup() && ($this->getCompanyGroup() === $company->getCompanyGroup());
    }

    public function getCurrentStatus(): ?CompanyStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param CompanyStatus|StatusInterface $currentStatus
     */
    public function setCurrentStatus(StatusInterface $currentStatus): Company
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @Groups({"company:read"})
     */
    public function hasSigned(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_SIGNED === $currentStatus->getStatus();
    }

    public function hasRefused(): bool
    {
        /** @var CompanyStatus $currentStatus */
        $currentStatus = $this->getCurrentStatus();

        return $currentStatus && CompanyStatus::STATUS_REFUSED === $currentStatus->getStatus();
    }

    public function getModule(string $module): CompanyModule
    {
        return $this->modules[$module];
    }

    public function hasModuleActivated(string $module): bool
    {
        return isset($this->modules[$module]) && $this->modules[$module]->isActivated();
    }

    /**
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

        return \array_values($activatedModuleCodes->toArray());
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

    public function getClientNumber(): ?string
    {
        return $this->clientNumber;
    }

    public function setClientNumber(?string $clientNumber): Company
    {
        $this->clientNumber = $clientNumber;

        return $this;
    }

    /**
     * @Groups({"company:read"})
     */
    public function getGroupName(): ?string
    {
        return $this->companyGroup ? $this->companyGroup->getName() : null;
    }

    public function getCompanyGroup(): ?CompanyGroup
    {
        return $this->companyGroup;
    }

    public function setCompanyGroup(?CompanyGroup $companyGroup): Company
    {
        $this->companyGroup = $companyGroup;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): Company
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getApplicableVat(): ?string
    {
        return $this->applicableVat;
    }

    public function setApplicableVat(?string $applicableVat): Company
    {
        $this->applicableVat = $applicableVat;

        return $this;
    }

    /**
     * @Groups({"company:read"})
     */
    public function isEligibleParticipant(): bool
    {
        return !\in_array($this->shortCode, self::NON_ELIGIBLE_TO_PARTICIPANT);
    }

    /**
     * CAG means Crédit Agricole Group.
     */
    public function isCAGMember(): bool
    {
        return CompanyGroup::COMPANY_GROUP_CA === $this->getGroupName();
    }

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
     * @return CompanyGroupTag[]|array
     */
    public function getCompanyGroupTags(): array
    {
        return $this->companyGroup ? $this->companyGroup->getTags() : [];
    }

    private function getCurrentStatusAt(DateTimeInterface $dateTime): ?CompanyStatus
    {
        /** @var CompanyStatus $status */
        $previousStatuses = $this->getStatuses()->filter(static function ($status) use ($dateTime) {
            return $status->getAdded() <= $dateTime;
        });

        return $previousStatuses ? $previousStatuses->last() : null;
    }
}
