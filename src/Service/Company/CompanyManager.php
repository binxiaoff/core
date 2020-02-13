<?php

declare(strict_types=1);

namespace Unilend\Service\Company;

use DomainException;
use Unilend\Entity\Company;
use Unilend\Repository\CompanyRepository;

class CompanyManager
{
    /** @var CompanyRepository */
    private $companyRepository;

    /**
     * @param CompanyRepository $companyRepository
     */
    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * @param string $email
     *
     * @return Company
     */
    public function getCompanyByEmail(string $email): Company
    {
        $guestEmailDomain = explode('@', mb_strtolower($email))[1];

        $company = $this->companyRepository->findOneBy(['emailDomain' => $guestEmailDomain]);

        if (null === $company) {
            throw new DomainException(sprintf('The email %s is not one of our partners.', $email));
        }

        return $company;
    }
}
