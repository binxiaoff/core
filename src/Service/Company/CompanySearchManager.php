<?php

declare(strict_types=1);

namespace Unilend\Service\Company;

use Unilend\Entity\Company;
use Unilend\Repository\CompanyRepository;
use Unilend\Service\WebServiceClient\InseeManager;

class CompanySearchManager
{
    /** @var CompanyRepository */
    private $companyRepository;
    /** @var InseeManager */
    private $inseeManager;

    /**
     * @param CompanyRepository $companyRepository
     * @param InseeManager      $inseeManager
     */
    public function __construct(CompanyRepository $companyRepository, InseeManager $inseeManager)
    {
        $this->companyRepository = $companyRepository;
        $this->inseeManager      = $inseeManager;
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
     * @return Company|null
     */
    private function searchCompanyBySiren(string $siren): ?array
    {
        return $this->inseeManager->searchBySirenNumber($siren);
    }

    /**
     * @param string $term
     *
     * @return array
     */
    private function searchCompaniesByName(string $term): array
    {
        return array_merge(
            $this->companyRepository->findByName($term, 5),
            $this->inseeManager->searchByName($term)
        );
    }
}
