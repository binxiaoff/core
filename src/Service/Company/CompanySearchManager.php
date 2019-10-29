<?php

declare(strict_types=1);

namespace Unilend\Service\Company;

use Unilend\Entity\Companies;
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
        return preg_match('/^\d{9}$/', $term) ?
            [$this->searchCompanyBySiren($term)] : $this->searchCompaniesByName($term);
    }

    /**
     * @param string $siren
     *
     * @return Companies|null
     */
    public function searchCompanyBySiren(string $siren): ?Companies
    {
        if ($companyData = $this->inseeManager->searchBySirenNumber($siren)) {
            return $this->createCompany($companyData);
        }

        return null;
    }

    /**
     * @param string $term
     *
     * @return array
     */
    private function searchCompaniesByName(string $term): array
    {
        return array_merge(
            $this->companiesRepository->findByName($term, 5),
            array_map([$this, 'createCompany'], $this->inseeManager->searchByName($term))
        );
    }

    /**
     * @param array $companyData
     *
     * @return Companies
     */
    private function createCompany(array $companyData): Companies
    {
        return new Companies($companyData['name'], $companyData['siren']);
    }
}
