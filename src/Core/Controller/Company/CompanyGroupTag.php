<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Company;

use Unilend\Core\Entity\Company;

class CompanyGroupTag
{
    /**
     * @param Company $data
     *
     * @return CompanyGroupTag[]|iterable
     */
    public function __invoke(Company $data): iterable
    {
        return $data->getCompanyGroupTags();
    }
}
