<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Closure;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\DTO\ProgramBorrowerTypeAllocationInput;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\EquivalenceCheckerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:programBorrowerTypeAllocation:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
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
 *             "input": ProgramBorrowerTypeAllocationInput::class,
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {"security": "is_granted('delete', object)"},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "input": ProgramBorrowerTypeAllocationInput::class,
 *             "validation_groups": {"creditGuaranty:programBorrowerTypeAllocation:createValidation"},
 *         },
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_borrower_type_allocation",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_program", "id_program_choice_option"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"program", "programChoiceOption"})
 */
class ProgramBorrowerTypeAllocation implements EquivalenceCheckerInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="programBorrowerTypeAllocations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_program_choice_option", nullable=false, onDelete="CASCADE")
     */
    private ProgramChoiceOption $programChoiceOption;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     *
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
     */
    private string $maxAllocationRate;

    public function __construct(Program $program, ProgramChoiceOption $programChoiceOption, string $maxAllocationRate)
    {
        $this->program             = $program;
        $this->programChoiceOption = $programChoiceOption;
        $this->maxAllocationRate   = $maxAllocationRate;
        $this->added               = new DateTimeImmutable();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function setProgram(Program $program): ProgramBorrowerTypeAllocation
    {
        $this->program = $program;

        return $this;
    }

    public function getProgramChoiceOption(): ProgramChoiceOption
    {
        return $this->programChoiceOption;
    }

    public function setProgramChoiceOption(ProgramChoiceOption $programChoiceOption): ProgramBorrowerTypeAllocation
    {
        $this->programChoiceOption = $programChoiceOption;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
     */
    public function getBorrowerType(): string
    {
        return $this->getProgramChoiceOption()->getDescription();
    }

    public function getMaxAllocationRate(): string
    {
        return $this->maxAllocationRate;
    }

    public function setMaxAllocationRate(string $maxAllocationRate): ProgramBorrowerTypeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }

    public function getEquivalenceChecker(): Closure
    {
        $self = $this;

        return static function (int $key, ProgramBorrowerTypeAllocation $pbta) use ($self): bool {
            return $pbta->getProgram()             === $self->getProgram()
                && $pbta->getProgramChoiceOption() === $self->getProgramChoiceOption();
        };
    }
}
