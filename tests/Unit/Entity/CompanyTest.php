<?php

namespace Unit\Entity;

use PHPUnit\Framework\TestCase;
use Unilend\Entity\Company;
use Unilend\Entity\CompanyModule;

class CompanyTest extends TestCase
{

    /**
     * Tests that modules are indexed by module name
     */
    public function testModules()
    {
        $codes = CompanyModule::getAvailableModuleCodes();
        $company = new Company('Fake', 'fake');
        $this->assertEquals(count($codes), $company->getModules()->count());
        // We expect the array of module to be indexed by module name
        $this->assertArrayHasKey(CompanyModule::MODULE_PARTICIPATION, $company->getModules()->toArray());
        $this->assertEquals(CompanyModule::MODULE_PARTICIPATION, $company->getModule(CompanyModule::MODULE_PARTICIPATION)->getCode());
    }
}
