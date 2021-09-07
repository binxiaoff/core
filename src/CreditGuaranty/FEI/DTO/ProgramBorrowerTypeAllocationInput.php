<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DTO;

use KLS\CreditGuaranty\FEI\Entity\Program;
use Symfony\Component\Validator\Constraints as Assert;

class ProgramBorrowerTypeAllocationInput
{
    /**
     * @Assert\NotBlank(groups={"creditGuaranty:programBorrowerTypeAllocation:createValidation"})
     */
    public Program $program;

    /**
     * @Assert\NotBlank(groups={"creditGuaranty:programBorrowerTypeAllocation:createValidation"})
     */
    public string $borrowerType;

    /**
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     */
    public string $maxAllocationRate;
}
