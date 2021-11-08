<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\Company;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use KLS\Test\Core\DataFixtures\Companies\BarCompanyFixtures;
use KLS\Test\Core\DataFixtures\Companies\BasicCompanyFixtures;
use KLS\Test\Core\DataFixtures\UserFixtures;

class StaffPermissionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            BarCompanyFixtures::class,
            BasicCompanyFixtures::class,
            UserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Managing company
        /** @var Company $company */
        $company = $this->getReference('company:bar');

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(
                    StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS
                    | StaffPermission::PERMISSION_REPORTING
                )
            );

            $manager->persist($staffPermission);
        }

        // Participant company
        /** @var Company $company */
        $company = $this->getReference('company:basic');

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)
            );

            $manager->persist($staffPermission);
        }

        $manager->flush();
    }
}
