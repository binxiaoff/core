<?php

declare(strict_types=1);

namespace KLS\Test\Core\DataFixtures\CompanyGroups;

use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;

class FooCompanyGroupFixtures extends AbstractCompanyGroupFixtures
{
    protected function getName(): string
    {
        return 'foo';
    }

    protected function getTags(CompanyGroup $companyGroup): array
    {
        return \array_map(static fn ($label) => new CompanyGroupTag($companyGroup, $label), [
            'pro',
            'ppp',
            'agriculture',
        ]);
    }
}
