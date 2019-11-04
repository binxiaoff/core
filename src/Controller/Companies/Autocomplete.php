<?php

declare(strict_types=1);

namespace Unilend\Controller\Companies;

use Unilend\Service\Company\CompanySearchManager;

class Autocomplete
{
    /** @var CompanySearchManager */
    private $companySearchManager;

    /**
     * @param CompanySearchManager $companySearchManager
     */
    public function __construct(CompanySearchManager $companySearchManager)
    {
        $this->companySearchManager = $companySearchManager;
    }

    /**
     * @param string $term
     *
     * @return array
     */
    public function __invoke(string $term)
    {
        return $this->companySearchManager->fetchCompanies($term);
    }
}
