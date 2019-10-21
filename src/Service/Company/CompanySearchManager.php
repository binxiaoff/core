<?php

declare(strict_types=1);

namespace Unilend\Service\Company;

use Unilend\Repository\CompaniesRepository;
use Unilend\Service\WebServiceClient\InseeManager;

class CompanySearchManager
{
    /** @var CompaniesRepository */
    private $companiesRepository;
    /** @var InseeManager */
    private $inseeManager;

    /**
     * @param CompaniesRepository $companiesRepository
     * @param InseeManager        $inseeManager
     */
    public function __construct(CompaniesRepository $companiesRepository, InseeManager $inseeManager)
    {
        $this->companiesRepository = $companiesRepository;
        $this->inseeManager        = $inseeManager;
    }

    /**
     * @param string $term
     *
     * @return array
     */
    public function fetchCompanies(string $term): array
    {
        if (preg_match('/^\d{9}$/', $term)) {
            $result = [$this->searchCompanyBySiren($term)];
        } else {
            $result = $this->searchCompaniesByName($term);
        }

        return $result;
    }

    /**
     * @param string $siren
     *
     * @return array|null
     */
    private function searchCompanyBySiren(string $siren): ?array
    {
        $company = $this->companiesRepository->findOneBy(['siren' => $siren]);

        if ($company) {
            return ['siren' => $company->getSiren(), 'name' => $company->getName()];
        }

        $result = $this->inseeManager->searchBySirenNumber($siren);

        return $result ? $result : null;
    }

    /**
     * @param string $term
     *
     * @return array
     */
    private function searchCompaniesByName(string $term): array
    {
        $companies    = $this->companiesRepository->findFiveByName($term);
        $companiesAPI = $this->inseeManager->searchByName($term);

        if (false === empty($companiesAPI)) {
            $companies = array_merge($companies, $companiesAPI);
        }

        return $companies;
    }
}
