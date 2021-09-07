<?php

declare(strict_types=1);

namespace KLS\Test\Core\DataFixtures\Companies;

class BarCompanyFixtures extends FooCompanyFixtures
{
    public function getName(): string
    {
        return 'bar';
    }
}
