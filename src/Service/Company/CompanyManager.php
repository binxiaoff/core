<?php

declare(strict_types=1);

namespace Unilend\Service\Company;

use Unilend\Entity\Companies;
use Unilend\Repository\CompaniesRepository;

class CompanyManager
{
    /** @var CompaniesRepository */
    private $companiesRepository;

    /**
     * @param CompaniesRepository $companiesRepository
     */
    public function __construct(CompaniesRepository $companiesRepository)
    {
        $this->companiesRepository = $companiesRepository;
    }

    /**
     * @param string $email
     *
     * @return Companies|null
     */
    public function getCompanyByEmail(string $email): ?Companies
    {
        $guestEmailDomain = explode('@', mb_strtolower($email))[1];

        return $this->companiesRepository->findOneBy(['emailDomain' => $guestEmailDomain]);
    }
}
