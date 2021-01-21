<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Unilend\Core\Entity\File;

class ProgramFile
{
    private Program $program;
    private File $file;
    private string $type;
}
