<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Interfaces\DriveCarrierInterface;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableUserAddedTrait;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Service\MoneyCalculator;
use KLS\Core\Validator\Constraints\PreviousValue;
use KLS\CreditGuaranty\FEI\Controller\Reporting\Download;
use KLS\CreditGuaranty\FEI\Controller\Reporting\FinancingObject\BulkUpdate;
use KLS\CreditGuaranty\FEI\Controller\Reporting\Update;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\DeepCloneInterface;
use KLS\CreditGuaranty\FEI\Validator\Constraints\IsGrossSubsidyEquivalentConfigured;
use LogicException;
use RuntimeException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:program:read",
 *             "creditGuaranty:programStatus:read",
 *             "timestampable:read",
 *             "money:read",
 *             "nullableMoney:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:program:write",
 *             "money:write",
 *             "nullableMoney:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *         "get_program_dataroom": {
 *             "method": "GET",
 *             "path": "/credit_guaranty/program/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('view', object)",
 *             "controller": Get::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "item-get_program_dataroom-read",
 *             },
 *         },
 *         "post_program_dataroom": {
 *             "method": "POST",
 *             "path": "/credit_guaranty/program/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('dataroom', object)",
 *             "deserialize": false,
 *             "controller": Post::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "item-post_program_dataroom-read",
 *             },
 *         },
 *         "delete_program_dataroom": {
 *             "method": "DELETE",
 *             "path": "/credit_guaranty/program/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('dataroom', object)",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *             },
 *         },
 *         "reporting_import_file_download": {
 *             "method": "GET",
 *             "controller": Download::class,
 *             "path": "/credit_guaranty/programs/{publicId}/reporting/import-file/download",
 *             "security": "is_granted('reporting', object)",
 *             "output_formats": { "xlsx" },
 *         },
 *         "reporting_import_file_upload": {
 *             "method": "POST",
 *             "controller": Update::class,
 *             "path": "/credit_guaranty/programs/{publicId}/reporting/import-file/upload",
 *             "security": "is_granted('reporting', object)",
 *             "input_formats": { "xlsx" },
 *             "deserialize": false,
 *         },
 *         "bulk_update_financing_objects": {
 *             "method": "PATCH",
 *             "controller": BulkUpdate::class,
 *             "path": "/credit_guaranty/programs/{publicId}/financing_objects",
 *             "security": "is_granted('reporting', object)",
 *             "output": false,
 *             "openapi_context": {
 *                 "parameters": {
 *                     {
 *                         "in": "query",
 *                         "name": "id",
 *                         "type": "integer",
 *                         "required": false,
 *                     },
 *                     {
 *                         "in": "query",
 *                         "name": "id[]",
 *                         "schema": {
 *                             "type": "array",
 *                             "items": {"type": "integer"},
 *                             "collectionFormat": "multi",
 *                         },
 *                         "required": false,
 *                     },
 *                     {
 *                         "in": "query",
 *                         "name": "itemsPerPage",
 *                         "type": "integer",
 *                         "description": "The items number per page (100 by default)",
 *                         "required": false,
 *                     },
 *                     {
 *                         "in": "query",
 *                         "name": "page",
 *                         "type": "integer",
 *                         "description": "The page number (1 by default)",
 *                         "required": false,
 *                     },
 *                     {
 *                         "in": "query",
 *                         "name": "search",
 *                         "type": "string",
 *                         "description": "The search filter on text values of reporting",
 *                         "required": false,
 *                     },
 *                     {
 *                         "in": "query",
 *                         "name": "order",
 *                         "schema": {
 *                             "type": "object",
 *                         },
 *                         "style": "deepObject",
 *                         "explode": true,
 *                         "description": "The order filter (i.e. order[field_alias]=direction)",
 *                         "required": false,
 *                     },
 *                 },
 *                 "requestBody": {
 *                     "content": {
 *                         "application/x-www-form-urlencoded": {
 *                             "schema": {
 *                                 "type": "object",
 *                                 "properties": {
 *                                     "reportingFirstDate": {"type": "date"},
 *                                     "reportingLastDate": {"type": "date"},
 *                                     "reportingValidationDate": {"type": "date"},
 *                                 },
 *                             },
 *                         },
 *                     },
 *                 },
 *                 "responses": {
 *                     "200": {
 *                         "description": "OK",
 *                         "content": {
 *                             "application/json": {
 *                                 "schema": {"type": "object"},
 *                             },
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "openapi_context": {
 *                 "parameters": {
 *                     {
 *                         "in": "query",
 *                         "name": "import",
 *                         "schema": {
 *                             "type": "string",
 *                             "minimum": 0,
 *                             "maximum": 1
 *                         },
 *                         "description": "Public id of the existing program from which you want to copy"
 *                     },
 *                 },
 *             },
 *         },
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:program:list",
 *                     "creditGuaranty:programStatus:read",
 *                     "timestampable:read",
 *                     "money:read",
 *                     "nullableMoney:read",
 *                 },
 *                 "openapi_definition_name": "collection-get-list",
 *             },
 *         },
 *     },
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"name"}, message="CreditGuaranty.Program.name.unique")
 */
class Program implements TraceableStatusAwareInterface, DriveCarrierInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableUserAddedTrait;
    use CloneableTrait;

    public const COMPANY_GROUP_TAG_CORPORATE   = 'corporate';
    public const COMPANY_GROUP_TAG_AGRICULTURE = 'agriculture';

    private const RATING_MODEL_DEFAULT = 'Banque de France';

    /**
     * @ORM\Column(length=100, unique=true)
     *
     * @Groups({"creditGuaranty:program:list", "creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private string $name;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_managing_company", nullable=false)
     *
     * @ApiProperty(readableLink=false)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:create"})
     */
    private Company $managingCompany;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\CompanyGroupTag")
     * @ORM\JoinColumn(name="id_company_group_tag", nullable=false)
     *
     * @Assert\Expression(
     *     "this.isCompanyGroupTagValid()",
     *     message="CreditGuaranty.Program.companyGroupTag.invalid"
     * )
     *
     * @Groups({"creditGuaranty:program:list", "creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private CompanyGroupTag $companyGroupTag;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isInDraft()", message="CreditGuaranty.Program.cappedAt.draft"),
     *     @Assert\Sequentially({
     *
     *         @PreviousValue\ScalarNotAssignable(message="CreditGuaranty.Program.cappedAt.notChangeable"),
     *         @PreviousValue\ScalarNotResettable(message="CreditGuaranty.Program.cappedAt.notChangeable"),
     *         @PreviousValue\NumericGreaterThanOrEqual(message="CreditGuaranty.Program.cappedAt.greater")
     *     })
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $cappedAt = null;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isInDraft()", message="CreditGuaranty.Program.funds.draft"),
     *
     *     @PreviousValue\MoneyGreaterThanOrEqual(message="CreditGuaranty.Program.funds.greater")
     * })
     *
     * @Groups({"creditGuaranty:program:list", "creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private Money $funds;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"creditGuaranty:program:list", "creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?DateTimeImmutable $distributionDeadline;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Count(max="10")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Length(max=200)
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?array $distributionProcess = null;

    /**
     * Duration in month.
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\GreaterThanOrEqual(1)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?int $guarantyDuration = null;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $guarantyCoverage = null;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $guarantyCost = null;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * The max of a signed "smallint" is 32767
     * @Assert\Range(min="1", max="32767")
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?int $reservationDuration = null;

    /**
     * @ORM\Column(length=60, nullable=true)
     *
     * @Assert\Choice(callback={CARatingType::class, "getConstList"})
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $ratingType = null;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private NullableMoney $maxFeiCredit;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @IsGrossSubsidyEquivalentConfigured
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?bool $esbCalculationActivated = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?bool $loanReleasedOnInvoice = null;

    /**
     * @ORM\Column(length=1200, nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $requestedDocumentsDescription = null;

    /**
     * @ORM\Column(length=16, nullable=false)
     *
     * @Groups({"creditGuaranty:program:read"})
     */
    private string $ratingModel;

    /**
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @MaxDepth(1)
     *
     * @Groups({"creditGuaranty:program:list", "creditGuaranty:program:read"})
     */
    private ?ProgramStatus $currentStatus;

    /**
     * @var Collection|ProgramStatus[]
     *
     * @Assert\Valid
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramStatus", mappedBy="program",
     *     orphanRemoval=true, cascade={"persist"}, fetch="EAGER"
     * )
     *
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"creditGuaranty:program:read"})
     */
    private Collection $statuses;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\Drive", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_drive", nullable=false, unique=true)
     */
    private Drive $drive;

    // Subresource

    /**
     * @var Collection|ProgramContact[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramContact", mappedBy="program",
     *     orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}
     * )
     */
    private Collection $programContacts;

    /**
     * @var Collection|ProgramGradeAllocation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramGradeAllocation",
     *     mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}, indexBy="grade"
     * )
     */
    private Collection $programGradeAllocations;

    /**
     * @var Collection|ProgramBorrowerTypeAllocation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramBorrowerTypeAllocation", mappedBy="program",
     *     orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}, indexBy="id_program_choice_option"
     * )
     */
    private Collection $programBorrowerTypeAllocations;

    /**
     * @var Collection|ProgramChoiceOption[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption", mappedBy="program",
     *     orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}
     * )
     */
    private Collection $programChoiceOptions;

    /**
     * @var Collection|ProgramEligibility[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramEligibility", mappedBy="program",
     *     orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}
     * )
     */
    private Collection $programEligibilities;

    /**
     * @var Collection|Participation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\Participation", mappedBy="program",
     *     orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}, indexBy="id_company"
     * )
     */
    private Collection $participations;

    /**
     * @var Collection|Reservation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\Reservation",
     *     mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}
     * )
     */
    private Collection $reservations;

    /**
     * @var Collection|ReportingTemplate[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReportingTemplate", mappedBy="program")
     */
    private Collection $reportingTemplates;

    public function __construct(string $name, CompanyGroupTag $companyGroupTag, Money $funds, Staff $addedBy)
    {
        $this->name                           = $name;
        $this->managingCompany                = $addedBy->getCompany();
        $this->companyGroupTag                = $companyGroupTag;
        $this->funds                          = $funds;
        $this->maxFeiCredit                   = new NullableMoney();
        $this->ratingModel                    = self::RATING_MODEL_DEFAULT;
        $this->drive                          = new Drive();
        $this->statuses                       = new ArrayCollection();
        $this->programGradeAllocations        = new ArrayCollection();
        $this->programBorrowerTypeAllocations = new ArrayCollection();
        $this->programChoiceOptions           = new ArrayCollection();
        $this->participations                 = new ArrayCollection();
        $this->reservations                   = new ArrayCollection();
        $this->reportingTemplates             = new ArrayCollection();
        $this->addedBy                        = $addedBy->getUser();
        $this->added                          = new DateTimeImmutable();
        $this->setCurrentStatus(new ProgramStatus($this, ProgramStatus::STATUS_DRAFT, $addedBy));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Program
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Program
    {
        $this->description = $description;

        return $this;
    }

    public function getManagingCompany(): Company
    {
        return $this->managingCompany;
    }

    public function getCompanyGroupTag(): CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

    public function getCappedAt(): ?string
    {
        return $this->cappedAt;
    }

    public function setCappedAt(?string $cappedAt): Program
    {
        $this->cappedAt = $cappedAt;

        return $this;
    }

    public function getFunds(): Money
    {
        return $this->funds;
    }

    public function setFunds(Money $funds): Program
    {
        $this->funds = $funds;

        return $this;
    }

    public function getDistributionDeadline(): ?DateTimeImmutable
    {
        return $this->distributionDeadline;
    }

    public function setDistributionDeadline(?DateTimeImmutable $distributionDeadline): Program
    {
        $this->distributionDeadline = $distributionDeadline;

        return $this;
    }

    public function getDistributionProcess(): ?array
    {
        return $this->distributionProcess;
    }

    public function setDistributionProcess(?array $distributionProcess): Program
    {
        $this->distributionProcess = $distributionProcess;

        return $this;
    }

    public function getGuarantyDuration(): ?int
    {
        return $this->guarantyDuration;
    }

    public function setGuarantyDuration(?int $guarantyDuration): Program
    {
        $this->guarantyDuration = $guarantyDuration;

        return $this;
    }

    public function getGuarantyCoverage(): ?string
    {
        return $this->guarantyCoverage;
    }

    public function setGuarantyCoverage(?string $guarantyCoverage): Program
    {
        $this->guarantyCoverage = $guarantyCoverage;

        return $this;
    }

    public function getGuarantyCost(): ?string
    {
        return $this->guarantyCost;
    }

    public function setGuarantyCost(?string $guarantyCost): Program
    {
        $this->guarantyCost = $guarantyCost;

        return $this;
    }

    public function getReservationDuration(): ?int
    {
        return $this->reservationDuration;
    }

    public function setReservationDuration(?int $reservationDuration): Program
    {
        $this->reservationDuration = $reservationDuration;

        return $this;
    }

    public function getRatingType(): ?string
    {
        return $this->ratingType;
    }

    /**
     * @return $this
     */
    public function setRatingType(?string $ratingType): Program
    {
        if ($ratingType !== $this->ratingType) {
            $this->programGradeAllocations->clear();
        }

        $this->ratingType = $ratingType;

        return $this;
    }

    public function getMaxFeiCredit(): NullableMoney
    {
        return $this->maxFeiCredit;
    }

    public function setMaxFeiCredit(NullableMoney $maxFeiCredit): Program
    {
        $this->maxFeiCredit = $maxFeiCredit;

        return $this;
    }

    public function isEsbCalculationActivated(): ?bool
    {
        return $this->esbCalculationActivated;
    }

    public function setEsbCalculationActivated(?bool $esbCalculationActivated): Program
    {
        $this->esbCalculationActivated = $esbCalculationActivated;

        return $this;
    }

    public function isLoanReleasedOnInvoice(): ?bool
    {
        return $this->loanReleasedOnInvoice;
    }

    public function setLoanReleasedOnInvoice(?bool $loanReleasedOnInvoice): Program
    {
        $this->loanReleasedOnInvoice = $loanReleasedOnInvoice;

        return $this;
    }

    public function getRequestedDocumentsDescription(): ?string
    {
        return $this->requestedDocumentsDescription;
    }

    public function setRequestedDocumentsDescription(?string $requestedDocumentsDescription): Program
    {
        $this->requestedDocumentsDescription = $requestedDocumentsDescription;

        return $this;
    }

    /**
     * @return Collection|ProgramStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    /**
     * @Groups({"creditGuaranty:program:list", "creditGuaranty:program:read"})
     */
    public function getDistributionDate(): ?DateTimeImmutable
    {
        foreach ($this->statuses as $status) {
            if (ProgramStatus::STATUS_DISTRIBUTED === $status->getStatus()) {
                return $status->getAdded();
            }
        }

        return null;
    }

    public function getCurrentStatus(): StatusInterface
    {
        return $this->currentStatus;
    }

    /**
     * @param StatusInterface|ProgramStatus $status
     */
    public function setCurrentStatus(StatusInterface $status): Program
    {
        $this->currentStatus = $status;

        return $this;
    }

    public function archive(Staff $archivedBy): Program
    {
        $this->setCurrentStatus(new ProgramStatus($this, ProgramStatus::STATUS_ARCHIVED, $archivedBy));

        return $this;
    }

    /**
     * Used in an expression constraints.
     */
    public function isCompanyGroupTagValid(): bool
    {
        $companyGroupTags = [self::COMPANY_GROUP_TAG_CORPORATE, self::COMPANY_GROUP_TAG_AGRICULTURE];

        return \in_array($this->getCompanyGroupTag()->getCode(), $companyGroupTags, true);
    }

    public function isInDraft(): bool
    {
        return ProgramStatus::STATUS_DRAFT === $this->getCurrentStatus()->getStatus();
    }

    public function isPaused(): bool
    {
        return ProgramStatus::STATUS_PAUSED === $this->getCurrentStatus()->getStatus();
    }

    public function isDistributed(): bool
    {
        return ProgramStatus::STATUS_DISTRIBUTED === $this->getCurrentStatus()->getStatus();
    }

    public function isArchived(): bool
    {
        return ProgramStatus::STATUS_ARCHIVED === $this->getCurrentStatus()->getStatus();
    }

    public function getDrive(): Drive
    {
        return $this->drive;
    }

    /**
     * @param Collection|ProgramContact[] $programContacts
     */
    public function setProgramContacts(Collection $programContacts): Program
    {
        $this->programContacts = $programContacts;

        return $this;
    }

    /**
     * @param Collection|ProgramGradeAllocation[] $programGradeAllocations
     */
    public function setProgramGradeAllocations(Collection $programGradeAllocations): Program
    {
        $this->programGradeAllocations = $programGradeAllocations;

        return $this;
    }

    /**
     * @return Collection|ProgramGradeAllocation[]
     */
    public function getProgramGradeAllocations(): Collection
    {
        return $this->programGradeAllocations;
    }

    /**
     * @return Collection|ProgramBorrowerTypeAllocation[]
     */
    public function getProgramBorrowerTypeAllocations(): Collection
    {
        return $this->programBorrowerTypeAllocations;
    }

    /**
     * @param Collection|ProgramBorrowerTypeAllocation[] $programBorrowerTypeAllocations
     */
    public function setProgramBorrowerTypeAllocations($programBorrowerTypeAllocations): Program
    {
        $this->programBorrowerTypeAllocations = $programBorrowerTypeAllocations;

        return $this;
    }

    public function removeProgramBorrowerTypeAllocation(
        ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation
    ): Program {
        $this->programBorrowerTypeAllocations->removeElement($programBorrowerTypeAllocation);

        return $this;
    }

    public function addProgramBorrowerTypeAllocation(
        ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation
    ): Program {
        $callback = static function (
            int $key,
            ProgramBorrowerTypeAllocation $pbta
        ) use ($programBorrowerTypeAllocation): bool {
            return $pbta->getProgramChoiceOption() === $programBorrowerTypeAllocation->getProgramChoiceOption();
        };
        if (
            $programBorrowerTypeAllocation->getProgram() === $this
            && false === $this->programBorrowerTypeAllocations->exists($callback)
        ) {
            $this->programBorrowerTypeAllocations->add($programBorrowerTypeAllocation);
        }

        return $this;
    }

    /**
     * @return Collection|ProgramChoiceOption[]
     */
    public function getProgramChoiceOptions(): Collection
    {
        return $this->programChoiceOptions;
    }

    /**
     * @param Collection|ProgramChoiceOption[] $programChoiceOptions
     */
    public function setProgramChoiceOptions($programChoiceOptions): Program
    {
        $this->programChoiceOptions = $programChoiceOptions;

        return $this;
    }

    public function addProgramChoiceOption(ProgramChoiceOption $programChoiceOption): Program
    {
        if (
            $programChoiceOption->getProgram() === $this
            && false === $this->programChoiceOptions->exists($programChoiceOption->getEquivalenceChecker())
        ) {
            $this->programChoiceOptions->add($programChoiceOption);
        }

        return $this;
    }

    /**
     * @return Collection|ProgramEligibility[]
     */
    public function getProgramEligibilities(): Collection
    {
        return $this->programEligibilities;
    }

    /**
     * @param Collection|ProgramEligibility[] $programEligibilities
     */
    public function setProgramEligibilities(Collection $programEligibilities): Program
    {
        $this->programEligibilities = $programEligibilities;

        return $this;
    }

    public function addProgramEligibility(ProgramEligibility $programEligibility): Program
    {
        $callback = fn (int $key, ProgramEligibility $pe): bool => $pe->getField() === $programEligibility->getField();

        if (
            $programEligibility->getProgram() === $this
            && false === $this->programEligibilities->exists($callback)
        ) {
            $this->programEligibilities->add($programEligibility);
        }

        return $this;
    }

    public function hasParticipant(Company $company): bool
    {
        return $this->getParticipants()->contains($company);
    }

    /**
     * @param Collection|Participation[] $participations
     */
    public function setParticipations(Collection $participations): Program
    {
        $this->participations = $participations;

        return $this;
    }

    /**
     * @return Collection|Participation[]
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations()
    {
        return $this->reservations;
    }

    /**
     * @return Collection|ReportingTemplate[]
     */
    public function getReportingTemplates(): Collection
    {
        return $this->reportingTemplates;
    }

    /**
     * @param array $filter Possible criteria for the filter are
     *                      - grade: borrower's grade
     *                      - borrowerType: borrower type (ProgramChoiceOption) id
     *                      - exclude: an array of project ids to exclude
     */
    public function getTotalProjectFunds(array $filter = []): Money
    {
        $totalProjectFunds = new Money($this->funds->getCurrency());

        foreach ($this->reservations as $reservation) {
            if ($reservation->isSent()) {
                if (false === $this->applyTotalProjectFundsFilters($reservation, $filter)) {
                    continue;
                }

                if (false === $reservation->getProject() instanceof Project) {
                    throw new RuntimeException(
                        \sprintf(
                            'Cannot find the project for reservation %d. Please check the data.',
                            $reservation->getId()
                        )
                    );
                }

                $fundingMoney      = $reservation->getProject()->getFundingMoney();
                $totalProjectFunds = MoneyCalculator::add($fundingMoney, $totalProjectFunds);
            }
        }

        return $totalProjectFunds;
    }

    public function duplicate(Staff $duplicatedBy): Program
    {
        $duplicatedProgram          = $this->deepClone();
        $duplicatedProgram->addedBy = $duplicatedBy->getUser();
        $duplicatedProgram->setCurrentStatus(
            new ProgramStatus($duplicatedProgram, ProgramStatus::STATUS_DRAFT, $duplicatedBy)
        );

        return $duplicatedProgram;
    }

    private function deepClone(): Program
    {
        $duplicatedProgram        = clone $this;
        $duplicatedProgram->drive = new Drive();
        $duplicatedProgram
            ->setProgramContacts($duplicatedProgram->cloneCollection($this->programContacts))
            ->setProgramChoiceOptions($duplicatedProgram->cloneCollection($this->programChoiceOptions))
            ->setProgramEligibilities($duplicatedProgram->cloneCollection($this->programEligibilities))
            ->setProgramGradeAllocations($duplicatedProgram->cloneCollection($this->programGradeAllocations))
            ->setProgramBorrowerTypeAllocations(
                $duplicatedProgram->cloneCollection($this->programBorrowerTypeAllocations)
            )
            ->setParticipations($duplicatedProgram->cloneCollection($this->participations))
        ;

        // Replace the ProgramChoiceOption (if not null)
        // in the ProgramEligibilityConfiguration and ProgramBorrowerTypeAllocation
        // (which are not remplace in the previous process) by the newly created one.
        // The new one was created with setProgramChoiceOptions() in the duplicated program.
        foreach ($this->getProgramEligibilities() as $programEligibility) {
            foreach ($programEligibility->getProgramEligibilityConfigurations() as $programEligibilityConfiguration) {
                $originalProgramChoiceOption = $programEligibilityConfiguration->getProgramChoiceOption();
                if ($originalProgramChoiceOption) {
                    $duplicatedProgramChoiceOption = $duplicatedProgram
                        ->findProgramChoiceOption($originalProgramChoiceOption)
                    ;
                    $programEligibilityConfiguration->setProgramChoiceOption($duplicatedProgramChoiceOption);
                }
            }
        }
        foreach ($this->getProgramBorrowerTypeAllocations() as $programBorrowerTypeAllocation) {
            $originalProgramChoiceOption = $programBorrowerTypeAllocation->getProgramChoiceOption();
            if ($originalProgramChoiceOption) {
                $duplicatedProgramChoiceOption = $duplicatedProgram
                    ->findProgramChoiceOption($originalProgramChoiceOption)
                ;
                $programBorrowerTypeAllocation->setProgramChoiceOption($duplicatedProgramChoiceOption);
            }
        }

        return $duplicatedProgram;
    }

    private function applyTotalProjectFundsFilters(Reservation $reservation, array $filter): bool
    {
        if (false === $reservation->getBorrower()->getBorrowerType() instanceof ProgramChoiceOption) {
            throw new RuntimeException(
                \sprintf(
                    'Cannot find the borrower type for reservation %d. Please check the data.',
                    $reservation->getId()
                )
            );
        }

        if (isset($filter['grade']) && $reservation->getBorrower()->getGrade() !== $filter['grade']) {
            return false;
        }

        $borrowerTypeId = $reservation->getBorrower()->getBorrowerType()->getId();

        if (isset($filter['borrowerType']) && $borrowerTypeId !== $filter['borrowerType']) {
            return false;
        }

        if (isset($filter['exclude'])) {
            if (\is_int($filter['exclude'])) {
                $filter['exclude'] = [$filter['exclude']];
            }
            if (\in_array($reservation->getProgram()->getId(), $filter['exclude'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * clone the OneToMany relation.
     */
    private function cloneCollection(Collection $collectionToDuplicate): ArrayCollection
    {
        $clonedArrayCollection = new ArrayCollection();
        foreach ($collectionToDuplicate as $item) {
            if ($item instanceof DeepCloneInterface) {
                $clonedItem = $item->deepClone();
            } else {
                $clonedItem = clone $item;
            }
            if (false === \method_exists($clonedItem, 'setProgram')) {
                throw new LogicException(
                    \sprintf(
                        'Cannot find the method setProgram of the class %s. Make sure it exists or it is accessible.',
                        \get_class($clonedItem)
                    )
                );
            }
            $clonedItem->setProgram($this);
            $clonedArrayCollection->add($clonedItem);
        }

        return $clonedArrayCollection;
    }

    /**
     * Find the choice option in the duplicated program which has the same attribut as the original one.
     */
    private function findProgramChoiceOption(ProgramChoiceOption $originalProgramChoiceOption): ProgramChoiceOption
    {
        $clonedProgramChoiceOption = $this->getProgramChoiceOptions()
            ->filter(
                fn (ProgramChoiceOption $item) => $item->getField() === $originalProgramChoiceOption->getField()
                    && $item->getDescription() === $originalProgramChoiceOption->getDescription()
            )
            ->first()
        ;
        if (false === $clonedProgramChoiceOption instanceof ProgramChoiceOption) {
            throw new LogicException(\sprintf(
                'The new program choice option cannot be found on program %s with field %s and description %s',
                $this->getName(),
                $originalProgramChoiceOption->getField()->getId(),
                $originalProgramChoiceOption->getDescription()
            ));
        }

        return $clonedProgramChoiceOption;
    }

    /**
     * @return Collection|Company[]
     */
    private function getParticipants(): Collection
    {
        return $this->participations->map(function (Participation $participation) {
            return $participation->getParticipant();
        });
    }
}
