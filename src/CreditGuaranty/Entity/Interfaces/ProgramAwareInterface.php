<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Entity\Interfaces;

use KLS\CreditGuaranty\Entity\Program;

interface ProgramAwareInterface
{
    public function getProgram(): Program;
}
