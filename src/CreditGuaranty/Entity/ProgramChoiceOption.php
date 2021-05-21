<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\ArchivableTrait;
use Unilend\Core\Entity\Traits\CloneableTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={"groups": {"creditGuaranty:programChoiceOption:read", "creditGuaranty:field:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:programChoiceOption:write"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"creditGuaranty:programChoiceOption:write", "creditGuaranty:programChoiceOption:create"}}
 *         }
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"field.publicId"})
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_choice_option",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"description", "id_field", "id_program"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"description", "field", "program"})
 */
class ProgramChoiceOption
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;
    use ArchivableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programChoiceOptions")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read", "creditGuaranty:programChoiceOption:create"})
     */
    private Program $program;

    /**
     * @ORM\Column(length=255)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read", "creditGuaranty:programChoiceOption:write"})
     *
     * @Assert\Expression("this.isDescriptionValid()")
     */
    private string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Field")
     * @ORM\JoinColumn(name="id_field", nullable=false)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read", "creditGuaranty:programChoiceOption:create"})
     */
    private Field $field;

    public function __construct(Program $program, string $description, Field $field)
    {
        $this->program     = $program;
        $this->description = $description;
        $this->field       = $field;
        $this->added       = new \DateTimeImmutable();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function setProgram(Program $program): ProgramChoiceOption
    {
        $this->program = $program;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): ProgramChoiceOption
    {
        $this->description = $description;

        return $this;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * If it's a pre-defined list, check whether the description is a pre-defined list's item.
     */
    public function isDescriptionValid(): bool
    {
        if (Field::TYPE_LIST === $this->getField()->getType() && null === $this->getField()->getPredefinedItems()) {
            return true;
        }

        return in_array($this->description, $this->getField()->getPredefinedItems(), true);
    }

    /**
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    public function getArchived(): ?DateTime
    {
        return $this->archived;
    }

    public function isArchived(): bool
    {
        return null !== $this->archived;
    }

    public function archive(): ProgramChoiceOption
    {
        $this->setArchived(new DateTime());

        return $this;
    }
}
