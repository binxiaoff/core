<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Traits\BlamableUserAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:reporting:read",
 *             "file:read"
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('view', object)",
 *             "denormalization_context": {
 *                 "groups": { "creditGuaranty:reporting:create" },
 *                 "openapi_definition_name": "collection-post-create",
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 * )
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_reporting")
 */
class Reporting
{
    use BlamableUserAddedTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    public const FILE_TYPE_CREDIT_GUARANTY_REPORTING = 'credit_guaranty_reporting';

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\File", cascade={"persist"})
     * @ORM\JoinColumn(name="id_file", nullable=false, unique=true, )
     *
     * @Groups("creditGuaranty:reporting:read")
     */
    private File $file;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReportingTemplate")
     * @ORM\JoinColumn(name="id_reporting_temmplate", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:reporting:read", "creditGuaranty:reporting:create"})
     */
    private ReportingTemplate $reportingTemplate;

    /**
     * @ORM\Column(type="json", name="filters", nullable=true)
     *
     * @Groups({"creditGuaranty:reporting:read", "creditGuaranty:reporting:create"})
     */
    private array $filters;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="added_by_company", referencedColumnName="id", nullable=true)
     */
    private ?Company $addedByCompany;

    public function __construct(
        ReportingTemplate $reportingTemplate,
        array $filters,
        ?User $addedBy,
        ?Company $addedByCompany = null
    ) {
        $this->reportingTemplate = $reportingTemplate;
        $this->filters           = $filters;
        $this->added             = new DateTimeImmutable();
        $this->addedBy           = $addedBy;
        $this->addedByCompany    = $addedByCompany;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function setFile(File $file): Reporting
    {
        $this->file = $file;

        return $this;
    }

    public function getReportingTemplate(): ReportingTemplate
    {
        return $this->reportingTemplate;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getAddedByCompany(): ?Company
    {
        return $this->addedByCompany;
    }
}
