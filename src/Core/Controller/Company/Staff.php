<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Company;

use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff as Entity;

class Staff
{
    /**
     * @return Entity[]|iterable
     */
    public function __invoke(Company $data): iterable
    {
        return $data->getStaff();
    }
}
