<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\Entity;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\{Company, CompanyModule};

class CompanyTest extends TestCase
{
    /**
     * Tests that modules are indexed by module name
     */
    public function testModules()
    {
        $codes = CompanyModule::getAvailableModuleCodes();
        $company = new Company('Fake', 'fake');
        static::assertEquals(count($codes), $company->getModules()->count());
        // We expect the array of module to be indexed by module name
        static::assertArrayHasKey(CompanyModule::MODULE_PARTICIPATION, $company->getModules()->toArray());
        static::assertEquals(CompanyModule::MODULE_PARTICIPATION, $company->getModule(CompanyModule::MODULE_PARTICIPATION)->getCode());
    }
}
