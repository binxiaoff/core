<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyModule;

/**
 * @internal
 *
 * @coversNothing
 */
class CompanyTest extends TestCase
{
    /**
     * Tests that modules are indexed by module name.
     */
    public function testModules()
    {
        $codes   = CompanyModule::getAvailableModuleCodes();
        $company = new Company('Fake', 'fake', '850890666');
        static::assertCount(count($codes), $company->getModules());
        // We expect the array of module to be indexed by module name
        static::assertArrayHasKey(CompanyModule::MODULE_PARTICIPATION, $company->getModules()->toArray());
        static::assertSame(CompanyModule::MODULE_PARTICIPATION, $company->getModule(CompanyModule::MODULE_PARTICIPATION)->getCode());
    }
}
