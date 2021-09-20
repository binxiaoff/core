<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\ArchivableTrait;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
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
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_reporting_template",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_program_reportingTemplate_name", columns={"id_program", "name"})
 *     }
 * )
 *
 * @UniqueEntity(fields={"program", "name"}, message="CreditGuaranty.Program.reportingTemplate.name.unique")
 *
 * @Gedmo\SoftDeleteable(fieldName="archived")
 */
class ReportingTemplate
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use ArchivableTrait;

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

    public function __construct(Program $program, string $name, Staff $addedBy)
    {
        $this->program = $program;
        $this->name    = $name;
        $this->addedBy = $addedBy;
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
