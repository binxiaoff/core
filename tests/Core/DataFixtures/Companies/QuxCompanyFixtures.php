<?php

declare(strict_types=1);

namespace KLS\Test\Core\DataFixtures\Companies;

class QuxCompanyFixtures extends FooCompanyFixtures
{
    public function getName(): string
    {
        return 'qux';
    }
}
