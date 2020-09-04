<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\Company;
use Unilend\Entity\CompanyModule;

class CompanyModuleFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ObjectManager $manager;

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->login(StaffFixtures::ADMIN);

        foreach ([...CompanyFixtures::COMPANIES, CompanyFixtures::CALS] as $referenceName) {
            /** @var Company $company */
            $company = $this->getReference($referenceName);
            $this->activateModules($company);
        }

        $this->manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [StaffFixtures::class, CompanyFixtures::class];
    }

    /**
     * @param Company $company
     * @param array   $excludeModules
     */
    private function activateModules(Company $company, array $excludeModules = [])
    {
        if (false === $company->isCAGMember()) {
            $excludeModules[] = CompanyModule::MODULE_ARRANGEMENT;
            $excludeModules[] = CompanyModule::MODULE_AGENCY;
        }

        if ($company->hasSigned()) {
            foreach (CompanyModule::getAvailableModuleCodes() as $moduleCode) {
                if (!\in_array($moduleCode, $excludeModules, true)) {
                    $module = $company->getModule($moduleCode);
                    $module->setActivated(true);
                    $this->manager->persist($module);
                }
            }
        }
    }
}
