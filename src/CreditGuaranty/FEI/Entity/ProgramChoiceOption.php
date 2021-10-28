<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Closure;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\ArchivableTrait;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\EquivalenceCheckerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:programChoiceOption:read",
 *             "creditGuaranty:field:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:programChoiceOption:write",
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
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:programChoiceOption:write",
 *                     "creditGuaranty:programChoiceOption:create",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *         },
 *     },
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"field.publicId", "field.fieldAlias"})
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
class ProgramChoiceOption implements EquivalenceCheckerInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;
    use ArchivableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="programChoiceOptions")
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
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Field")
     * @ORM\JoinColumn(name="id_field", nullable=false)
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

        return \in_array($this->description, $this->getField()->getPredefinedItems(), true);
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

    /**
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    public function getFieldAlias(): string
    {
        return $this->field->getFieldAlias();
    }

    public function getEquivalenceChecker(): Closure
    {
        $self = $this;

        return function (int $key, ProgramChoiceOption $pco) use ($self): bool {
            return $pco->getProgram()     === $self->getProgram()
                && $pco->getField()       === $self->getField()
                && $pco->getDescription() === $self->getDescription();
        };
    }

    /**
     * @Assert\Callback
     */
    public function validateNew(ExecutionContextInterface $context): void
    {
        if (null !== $this->id) {
            return;
        }

        if (
            \count($this->field->getPredefinedItems())
            && false === \in_array($this->description, $this->field->getPredefinedItems(), true)
        ) {
            $context->buildViolation('CreditGuaranty.ProgramChoiceOption.invalid')
                ->atPath('description')
                ->addViolation()
            ;
        }
    }
}
