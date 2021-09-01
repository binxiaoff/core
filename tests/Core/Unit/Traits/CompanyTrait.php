<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyStatus;

trait CompanyTrait
{
    private function createCompany(): Company
    {
        $company = new Company('displayName', 'CompanyName', 'siren');
        $company->setShortCode('KLS');
        $company->setEmailDomain('KLS');

        return $company;
    }

    private function createCompanyWithGroupAndStatus(): Company
    {
        $company = $this->createCompany();

        $company->setCompanyGroup($this->createCompanyGroup());

        $status = new CompanyStatus($company, 10);
        $company->setCurrentStatus($status);

        return $company;
    }

    private function createCompanyGroup(): CompanyGroup
    {
        return new CompanyGroup('group');
    }
}
