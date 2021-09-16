<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
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
 *             "security": "is_granted('create', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('create', object)"
 *         },
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_reporting_template_field")
 */
class ReportingTemplateField
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ReportingTemplate", inversedBy="reportingTemplateField")
     * @ORM\JoinColumn(name="id_reporting_template", nullable=false)
     *
     * @Groups({"creditGuaranty:reportingTemplateField:read", "creditGuaranty:reportingTemplateField:write"})
     */
    private ReportingTemplate $reportingTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Field", inversedBy="reportingTemplateField")
     * @ORM\JoinColumn(name="id_field", nullable=false)
     *
     * @Groups({"creditGuaranty:reportingTemplateField:read", "creditGuaranty:reportingTemplateField:write", "creditGuaranty:reportingTemplate:read"})
     */
    private Field $field;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"creditGuaranty:reportingTemplateField:read", "creditGuaranty:reportingTemplateField:write"})
     */
    private int $position;

    public function __construct(ReportingTemplate $reportingTemplate, Field $field, int $position)
    {
        $this->reportingTemplate = $reportingTemplate;
        $this->field             = $field;
        $this->position          = $position;
        $this->added             = new DateTimeImmutable();
    }

    public function getReportingTemplate(): ReportingTemplate
    {
        return $this->reportingTemplate;
    }

    public function setReportingTemplate(ReportingTemplate $reportingTemplate): ReportingTemplateField
    {
        $this->reportingTemplate = $reportingTemplate;

        return $this;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function setField(Field $field): ReportingTemplateField
    {
        $this->field = $field;

        return $this;
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
