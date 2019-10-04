<?php

declare(strict_types=1);

namespace Unilend\Service\Company;

use DomainException;
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
     * @return Companies
     */
    public function getCompanyByEmail(string $email): Companies
    {
        $guestEmailDomain = explode('@', mb_strtolower($email))[1];

        $company = $this->companiesRepository->findOneBy(['emailDomain' => $guestEmailDomain]);

        if (null === $company) {
            throw new DomainException(sprintf('The email %s is not one of our partners.', $email));
        }

        return $company;
    }
}
