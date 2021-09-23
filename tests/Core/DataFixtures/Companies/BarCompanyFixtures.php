<?php

declare(strict_types=1);

namespace KLS\Test\Core\DataFixtures\Companies;

use KLS\Core\Entity\CompanyGroup;

class BarCompanyFixtures extends FooCompanyFixtures
{
    public function getName(): string
    {
        return 'bar';
    }

    protected function getCompanyGroup(): ?CompanyGroup
    {
        return $this->getReference('companyGroup:foo');
    }
}
