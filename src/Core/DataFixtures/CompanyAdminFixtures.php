<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\CompanyAdmin;
use Unilend\Core\Entity\Staff;

class CompanyAdminFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            StaffFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $adminStaff */
        $adminStaff = $this->getReference(StaffFixtures::ADMIN);

        $companyAdmin = new CompanyAdmin($adminStaff->getUser(), $adminStaff->getCompany());

        $manager->persist($companyAdmin);
        $manager->flush();
    }
}
