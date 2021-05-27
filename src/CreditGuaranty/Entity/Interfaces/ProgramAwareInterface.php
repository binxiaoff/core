<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Interfaces;

use Unilend\CreditGuaranty\Entity\Program;

interface ProgramAwareInterface
{
    public function getProgram(): Program;
}
