<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class ProgramBorrowerTypeAllocation
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramBorrowerType")
     * @ORM\JoinColumn(name="id_program_borrower_type", nullable=false)
     */
    private ProgramBorrowerType $programBorrowerType;

    /**
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     */
    private string $maxAllocationRate;

    /**
     * @param Program             $program
     * @param ProgramBorrowerType $programBorrowerType
     * @param string              $maxAllocationRate
     */
    public function __construct(Program $program, ProgramBorrowerType $programBorrowerType, string $maxAllocationRate)
    {
        $this->program             = $program;
        $this->programBorrowerType = $programBorrowerType;
        $this->maxAllocationRate   = $maxAllocationRate;
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return ProgramBorrowerType
     */
    public function getProgramBorrowerType(): ProgramBorrowerType
    {
        return $this->programBorrowerType;
    }

    /**
     * @param ProgramBorrowerType $programBorrowerType
     *
     * @return ProgramBorrowerTypeAllocation
     */
    public function setProgramBorrowerType(ProgramBorrowerType $programBorrowerType): ProgramBorrowerTypeAllocation
    {
        $this->programBorrowerType = $programBorrowerType;

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
}
