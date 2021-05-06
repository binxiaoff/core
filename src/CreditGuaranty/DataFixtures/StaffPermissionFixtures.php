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
use Unilend\CreditGuaranty\Entity\StaffPermission;

class StaffPermissionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Company $company */
        $company       = $this->getReference(CompanyFixtures::CASA);
        $referenceUser = $this->getReference(UserFixtures::ADMIN);

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission($staff, StaffPermission::PERMISSION_READ_PROGRAM);
            if ($staff->getUser() === $referenceUser) {
                $staffPermission->addPermission(StaffPermission::PERMISSION_EDIT_PROGRAM | StaffPermission::PERMISSION_CREATE_PROGRAM);
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
