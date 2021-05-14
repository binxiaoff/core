<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\CompanyFixtures;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\DataFixtures\UserFixtures;
use Unilend\Core\Entity\Company;
use Unilend\Core\Model\Bitmask;
use Unilend\CreditGuaranty\Entity\StaffPermission;

class StaffPermissionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Managing company (CASA)
        /** @var Company $company */
        $company       = $this->getReference(CompanyFixtures::CASA);
        $referenceUser = $this->getReference(UserFixtures::ADMIN);

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(StaffPermission::PERMISSION_READ_PROGRAM
                    | StaffPermission::PERMISSION_EDIT_PROGRAM
                    | StaffPermission::PERMISSION_CREATE_PROGRAM
                | StaffPermission::PERMISSION_READ_RESERVATION)
            );
            if ($staff->getUser() === $referenceUser) {
                $staffPermission->setGrantPermissions(
                    StaffPermission::PERMISSION_GRANT_READ_PROGRAM
                    | StaffPermission::PERMISSION_GRANT_CREATE_PROGRAM
                    | StaffPermission::PERMISSION_GRANT_EDIT_PROGRAM
                    | StaffPermission::PERMISSION_GRANT_READ_RESERVATION
                );
            }

            $manager->persist($staffPermission);
        }

        // participant (CR)
        /** @var Company $company */
        $company = $this->getReference(CompanyFixtures::COMPANY_MANY_STAFF);
        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(StaffPermission::PERMISSION_READ_RESERVATION
                    | StaffPermission::PERMISSION_EDIT_RESERVATION
                    | StaffPermission::PERMISSION_CREATE_RESERVATION
                | StaffPermission::PERMISSION_READ_PROGRAM)
            );
            if ($staff->getUser() === $referenceUser) {
                $staffPermission->setGrantPermissions(
                    StaffPermission::PERMISSION_GRANT_READ_RESERVATION
                    | StaffPermission::PERMISSION_GRANT_CREATE_RESERVATION
                    | StaffPermission::PERMISSION_GRANT_EDIT_RESERVATION
                    | StaffPermission::PERMISSION_GRANT_READ_PROGRAM
                );
            }

            $manager->persist($staffPermission);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
            StaffFixtures::class,
        ];
    }
}
