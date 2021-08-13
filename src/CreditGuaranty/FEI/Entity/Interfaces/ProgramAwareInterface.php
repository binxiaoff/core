<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Interfaces;

use KLS\CreditGuaranty\FEI\Entity\Program;

interface ProgramAwareInterface
{
    public function getProgram(): Program;
}
