<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:reportingTemplateField:read"
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:reportingTemplateField:write"
 *         },
 *         "openapi_definition_name": "write",
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
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_reporting_template_field",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniq_program_reportingTemplateField_field_reporting_template",
 *             columns={"id_reporting_template", "id_field"}
 *         )
 *     }
 * )
 * @UniqueEntity(fields={"reportingTemplate", "field"}, message="CreditGuaranty.Program.reportingTemplateField.field.unique")
 */
class ReportingTemplateField
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReportingTemplate", inversedBy="reportingTemplateFields")
     * @ORM\JoinColumn(name="id_reporting_template", nullable=false)
     *
     * @Gedmo\SortableGroup
     *
     * @Groups({"creditGuaranty:reportingTemplateField:read", "creditGuaranty:reportingTemplateField:write"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private ReportingTemplate $reportingTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Field")
     * @ORM\JoinColumn(name="id_field", nullable=false)
     *
     * @Groups({"creditGuaranty:reportingTemplateField:read", "creditGuaranty:reportingTemplateField:write"})
     */
    private Field $field;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     *
     * @Assert\PositiveOrZero
     *
     * @Gedmo\SortablePosition
     *
     * @Groups({"creditGuaranty:reportingTemplateField:read", "creditGuaranty:reportingTemplateField:write"})
     */
    private int $position;

    public function __construct(ReportingTemplate $reportingTemplate, Field $field)
    {
        $this->reportingTemplate = $reportingTemplate;
        $this->field             = $field;
        $this->added             = new DateTimeImmutable();
    }

    public function getReportingTemplate(): ReportingTemplate
    {
        return $this->reportingTemplate;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): ReportingTemplateField
    {
        $this->position = $position;

        return $this;
    }
}
