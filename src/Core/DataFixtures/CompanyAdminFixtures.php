<?php

declare(strict_types=1);

namespace KLS\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\CompanyAdmin;
use KLS\Core\Entity\Staff;

class CompanyAdminFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return [
            StaffFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        /** @var Staff $adminStaff */
        $adminStaff = $this->getReference(StaffFixtures::ADMIN);

        $companyAdmin = new CompanyAdmin($adminStaff->getUser(), $adminStaff->getCompany());

        $manager->persist($companyAdmin);
        $manager->flush();
    }
}
