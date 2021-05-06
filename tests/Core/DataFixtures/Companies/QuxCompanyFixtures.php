<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

class QuxCompanyFixtures extends FooCompanyFixtures
{
    public function getName(): string
    {
        return 'qux';
    }
}
