<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Company;

use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team as Entity;

class Team
{
    /**
     * @return Entity[]|iterable
     */
    public function __invoke(Company $data): iterable
    {
        return $data->getTeams();
    }
}
