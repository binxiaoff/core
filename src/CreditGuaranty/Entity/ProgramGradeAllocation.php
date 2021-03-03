<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class ProgramGradeAllocation
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     */
    private Program $program;

    /**
     * @ORM\Column(length=10)
     */
    private string $grade;

    /**
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     */
    private string $maxAllocationRate;

    /**
     * @param Program $program
     * @param string  $grade
     * @param string  $maxAllocationRate
     */
    public function __construct(Program $program, string $grade, string $maxAllocationRate)
    {
        $this->program           = $program;
        $this->grade             = $grade;
        $this->maxAllocationRate = $maxAllocationRate;
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
    public function getGrade(): string
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     *
     * @return ProgramGradeAllocation
     */
    public function setGrade(string $grade): ProgramGradeAllocation
    {
        $this->grade = $grade;

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
     * @return ProgramGradeAllocation
     */
    public function setMaxAllocationRate(string $maxAllocationRate): ProgramGradeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }
}
