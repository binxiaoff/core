<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Company;

use Unilend\Core\Entity\Company;
use Unilend\Core\Repository\TeamRepository;

class Team
{
    /**
     * @param Company $data
     *
     * @return Team[]|iterable
     */
    public function __invoke(Company $data): iterable
    {
        return $data->getTeams();
    }
}
