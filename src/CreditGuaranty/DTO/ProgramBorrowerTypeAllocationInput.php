<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Unilend\CreditGuaranty\Entity\Program;

class ProgramBorrowerTypeAllocationInput
{
    /**
     * @Assert\NotBlank
     */
    public Program $program;

    /**
     * @Assert\NotBlank
     */
    public string $borrowerType;

    /**
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     */
    public string $maxAllocationRate;
}
