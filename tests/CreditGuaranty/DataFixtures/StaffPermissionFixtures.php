<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\Company;
use Unilend\Core\Model\Bitmask;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;
use Unilend\Test\Core\DataFixtures\Companies\BarCompanyFixtures;
use Unilend\Test\Core\DataFixtures\Companies\BasicCompanyFixtures;
use Unilend\Test\Core\DataFixtures\UserFixtures;

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
                new Bitmask(StaffPermission::PERMISSION_READ_PROGRAM
                    | StaffPermission::PERMISSION_EDIT_PROGRAM
                    | StaffPermission::PERMISSION_CREATE_PROGRAM
                | StaffPermission::PERMISSION_READ_RESERVATION)
            );

            $manager->persist($staffPermission);
        }

        // Participant company
        /** @var Company $company */
        $company = $this->getReference('company:basic');

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(StaffPermission::PERMISSION_READ_RESERVATION
                    | StaffPermission::PERMISSION_EDIT_RESERVATION
                    | StaffPermission::PERMISSION_CREATE_RESERVATION
                | StaffPermission::PERMISSION_READ_PROGRAM)
            );

            $manager->persist($staffPermission);
        }

        $manager->flush();
    }
}
