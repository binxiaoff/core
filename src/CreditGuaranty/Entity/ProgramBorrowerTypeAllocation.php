<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\CloneableTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\CreditGuaranty\DTO\ProgramBorrowerTypeAllocationInput;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:programBorrowerTypeAllocation:read", "timestampable:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "input": ProgramBorrowerTypeAllocationInput::class,
 *             "security": "is_granted('edit', object)"
 *         },
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "input": ProgramBorrowerTypeAllocationInput::class,
 *             "validation_groups": {"creditGuaranty:programBorrowerTypeAllocation:createValidation"}
 *         }
 *     }
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
class ProgramBorrowerTypeAllocation
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programBorrowerTypeAllocations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_program_choice_option", nullable=false, onDelete="CASCADE")
     */
    private ProgramChoiceOption $programChoiceOption;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2)
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

    public function getMaxAllocationRate(): string
    {
        return $this->maxAllocationRate;
    }

    public function setMaxAllocationRate(string $maxAllocationRate): ProgramBorrowerTypeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
     */
    public function getBorrowerType(): string
    {
        return $this->getProgramChoiceOption()->getDescription();
    }
}
