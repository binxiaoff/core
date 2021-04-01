<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\CompanyGroups;

use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;

class FooCompanyGroupFixtures extends AbstractCompanyGroupFixtures
{
    /**
     * @inheritDoc
     */
    protected function getName(): string
    {
        return 'foo';
    }

    /**
     * @inheritDoc
     */
    protected function getTags(CompanyGroup $companyGroup): array
    {
        return array_map(static fn ($label) => new CompanyGroupTag($companyGroup, $label), [
            'pro',
            'ppp',
            'agriculture',
        ]);
    }
}
