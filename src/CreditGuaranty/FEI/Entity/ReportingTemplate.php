<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\ArchivableTrait;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\Controller\Reporting\Reporting;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:reportingTemplate:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:reportingTemplate:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *         "reporting": {
 *             "method": "GET",
 *             "path": "/credit_guaranty/reporting_templates/{publicId}/reporting",
 *             "controller": Reporting::class,
 *             "security": "is_granted('view', object)",
 *             "openapi_context": {
 *                 "parameters": {
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
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *     },
 * )
 * @ApiFilter(OrderFilter::class, properties={"name"})
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_reporting_template")
 *
 * @UniqueEntity(fields={"name", "program"}, message="CreditGuaranty.Program.reportingTemplate.name.unique")
 *
 * @Gedmo\SoftDeleteable(fieldName="archived")
 */
class ReportingTemplate
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use ArchivableTrait;

    public const IMPORT_FILE_COLUMNS = [
        self::GREEN_COLUMN,
        self::OPERATION_COLUMN,
        self::CRD_COLUMN,
        self::MATURITE_COLUMN,
    ];
    public const GREEN_COLUMN     = 'n° GREEN';
    public const OPERATION_COLUMN = 'n° d\'opération';
    public const CRD_COLUMN       = 'CRD';
    public const MATURITE_COLUMN  = 'Maturité';

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="reportingTemplates")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @Groups({"creditGuaranty:reportingTemplate:read", "creditGuaranty:reportingTemplate:write"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private Program $program;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=100)
     *
     * @Groups({"creditGuaranty:reportingTemplate:read", "creditGuaranty:reportingTemplate:write"})
     */
    private string $name;

    /**
     * @var Collection|ReportingTemplateField[]
     *
     * @ORM\OneToMany(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField", mappedBy="reportingTemplate")
     *
     * @Groups({"creditGuaranty:reportingTemplate:read"})
     */
    private Collection $reportingTemplateFields;

    public function __construct(Program $program, string $name, Staff $addedBy)
    {
        $this->program                 = $program;
        $this->name                    = $name;
        $this->addedBy                 = $addedBy;
        $this->added                   = new DateTimeImmutable();
        $this->reportingTemplateFields = new ArrayCollection();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ReportingTemplate
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|ReportingTemplateField[]
     */
    public function getReportingTemplateFields(): Collection
    {
        return $this->reportingTemplateFields;
    }
}
