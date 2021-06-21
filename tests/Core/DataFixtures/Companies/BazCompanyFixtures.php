<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

class BazCompanyFixtures extends FooCompanyFixtures
{
    public function getName(): string
    {
        return 'baz';
    }
}
