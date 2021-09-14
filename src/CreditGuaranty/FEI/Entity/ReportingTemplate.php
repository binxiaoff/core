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
 *             "creditGuaranty:reportingTemplate:read"
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:reportingTemplate:write"
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
 * @ORM\Table(name="credit_guaranty_reporting_template")
 */
class ReportingTemplate
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="reportingTemplates")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @Groups({"creditGuaranty:reportingTemplate:read", "creditGuaranty:reportingTemplate:write"})
     */
    private Program $program;

    /**
     * @ORM\Column(type="string", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"creditGuaranty:reportingTemplate:read", "creditGuaranty:reportingTemplate:write"})
     */
    private string $name;

    public function __construct(Program $program, string $name)
    {
        $this->program = $program;
        $this->name    = $name;
        $this->added   = new DateTimeImmutable();
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
}
