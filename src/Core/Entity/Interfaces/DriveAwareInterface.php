<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Interfaces;

use Unilend\Core\Entity\Drive;

interface DriveAwareInterface
{
    public function getDrive(): Drive;
}
