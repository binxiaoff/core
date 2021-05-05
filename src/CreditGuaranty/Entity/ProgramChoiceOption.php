<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiProperty, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ApiResource(
 *      attributes={"pagination_enabled": false},
 *      normalizationContext={"groups":{"creditGuaranty:programChoiceOption:read", "creditGuaranty:field:read", "timestampable:read"}},
 *      denormalizationContext={"groups":{"creditGuaranty:programChoiceOption:write", "creditGuaranty:field:read"}},
 *      itemOperations={
 *          "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *          },
 *          "patch",
 *          "delete"
 *      },
 *      collectionOperations={
 *          "post"
 *      }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"field.publicId"})
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_choice_option",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"description", "id_field", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"description", "field", "program"})
 */
class ProgramChoiceOption
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programChoiceOptions")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read", "creditGuaranty:programChoiceOption:write"})
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
     * @Groups({"creditGuaranty:programChoiceOption:read", "creditGuaranty:programChoiceOption:write"})
     */
    private Field $field;

    /**
     * @param Program $program
     * @param string  $description
     * @param Field   $field
     */
    public function __construct(Program $program, string $description, Field $field)
    {
        $this->program     = $program;
        $this->description = $description;
        $this->field       = $field;
        $this->added       = new \DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ProgramChoiceOption
     */
    public function setDescription(string $description): ProgramChoiceOption
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * If it's a pre-defined list, check whether the description is a pre-defined list's item.
     *
     * @return bool
     */
    public function isDescriptionValid(): bool
    {
        if (Field::TYPE_LIST === $this->getField()->getType() && null === $this->getField()->getPredefinedItems()) {
            return true;
        }

        return in_array($this->description, $this->getField()->getPredefinedItems(), true);
    }
}
