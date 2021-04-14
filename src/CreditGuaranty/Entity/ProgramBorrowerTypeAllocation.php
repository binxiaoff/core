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
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\CreditGuaranty\DTO\ProgramBorrowerTypeAllocationInput;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:programBorrowerTypeAllocation:read", "creditGuaranty:programChoiceOption:read", "timestampable:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "input": ProgramBorrowerTypeAllocationInput::class
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
     *
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
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

    /**
     * @param Program             $program
     * @param ProgramChoiceOption $programChoiceOption
     * @param string              $maxAllocationRate
     */
    public function __construct(Program $program, ProgramChoiceOption $programChoiceOption, string $maxAllocationRate)
    {
        $this->program             = $program;
        $this->programChoiceOption = $programChoiceOption;
        $this->maxAllocationRate   = $maxAllocationRate;
        $this->added               = new DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return ProgramChoiceOption
     */
    public function getProgramChoiceOption(): ProgramChoiceOption
    {
        return $this->programChoiceOption;
    }

    /**
     * @param ProgramChoiceOption $programChoiceOption
     *
     * @return ProgramBorrowerTypeAllocation
     */
    public function setProgramChoiceOption(ProgramChoiceOption $programChoiceOption): ProgramBorrowerTypeAllocation
    {
        $this->programChoiceOption = $programChoiceOption;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxAllocationRate(): string
    {
        return $this->maxAllocationRate;
    }

    /**
     * @param string $maxAllocationRate
     *
     * @return ProgramBorrowerTypeAllocation
     */
    public function setMaxAllocationRate(string $maxAllocationRate): ProgramBorrowerTypeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:programBorrowerTypeAllocation:read"})
     *
     * @return string
     */
    public function getBorrowerType(): string
    {
        return $this->getProgramChoiceOption()->getDescription();
    }
}
