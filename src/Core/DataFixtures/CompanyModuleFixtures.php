<?php

declare(strict_types=1);

namespace KLS\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyModule;
use KLS\Core\Entity\Embeddable\NullableMoney;

class CompanyModuleFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ObjectManager $entityManager;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
            CompanyFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->entityManager = $manager;
        $this->login(StaffFixtures::ADMIN);

        foreach ([...CompanyFixtures::COMPANIES, CompanyFixtures::KLS] as $referenceName) {
            /** @var Company $company */
            $company = $this->getReference($referenceName);
            $this->activateModules($company);
        }

        $this->entityManager->flush();
    }

    private function activateModules(Company $company, array $excludeModules = []): void
    {
        if (false === $company->isCAGMember()) {
            $excludeModules[] = CompanyModule::MODULE_ARRANGEMENT;
            $excludeModules[] = CompanyModule::MODULE_AGENCY;
        }

        if (false === $company->hasSigned()) {
            return;
        }

        foreach (CompanyModule::getAvailableModuleCodes() as $moduleCode) {
            if (\in_array($moduleCode, $excludeModules, true)) {
                continue;
            }

            $module = $company->getModule($moduleCode);
            $module->setActivated(true);

            if (CompanyModule::MODULE_ARRANGEMENT === $module->getCode()) {
                $module->setArrangementAnnualLicenseMoney(
                    $this->faker->boolean ? new NullableMoney('EUR', (string) $this->faker->randomNumber()) : null
                );
            }

            $this->entityManager->persist($module);
        }
    }
}
