<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\DataFixtures\UserFixtures;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\User;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;

class StaffPermissionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ObjectManager $manager;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        // Managing company (CASA)
        /** @var Company $company */
        $company = $this->getReference(CompanyFixtures::CASA);
        /** @var User $referenceUser */
        $referenceUser = $this->getReference(UserFixtures::ADMIN);

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)
            );

            if ($staff->getUser() === $referenceUser) {
                $staffPermission->setGrantPermissions(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS);
            }

            $manager->persist($staffPermission);
        }

        // Participant (CR)
        // we create the CG admin for CA banks.
        foreach (CompanyFixtures::CA_SHORTCODE as $companyName => $companyShortCode) {
            /** @var Company $company */
            $company = $this->getReference('company:' . $companyShortCode);
            $this->createAdminParticipant($company);
        }

        $manager->flush();
    }

    private function createAdminParticipant(Company $company): void
    {
        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)
            );
            // In the fixtures, there is only one staff per company, who is the admin
            $staffPermission->setGrantPermissions(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS);
            $this->manager->persist($staffPermission);
        }
    }
}
