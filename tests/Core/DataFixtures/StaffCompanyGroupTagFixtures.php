<?php

declare(strict_types=1);

namespace KLS\Test\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\Company;
use KLS\Test\Core\DataFixtures\Companies\FooCompanyFixtures;

class StaffCompanyGroupTagFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            FooCompanyFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Company $companyBar */
        $companyBar = $this->getReference('company:bar');
        /** @var Company $companyBasic */
        $companyBasic = $this->getReference('company:basic');

        foreach ([$companyBar, $companyBasic] as $company) {
            foreach ($company->getStaff() as $staff) {
                foreach ($company->getCompanyGroupTags() as $companyGroupTag) {
                    $staff->addCompanyGroupTag($companyGroupTag);
                    $manager->persist($staff);
                }
            }
        }

        $manager->flush();
    }
}
