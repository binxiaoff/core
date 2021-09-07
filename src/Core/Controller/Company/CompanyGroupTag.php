<?php

declare(strict_types=1);

namespace KLS\Core\Controller\Company;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroupTag as Entity;

class CompanyGroupTag
{
    /**
     * @return Entity[]|iterable
     */
    public function __invoke(Company $data): iterable
    {
        return $data->getCompanyGroupTags();
    }
}
