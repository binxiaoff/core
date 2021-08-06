<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\StaffStatus;
use Unilend\Core\Entity\User;

class StaffFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const ADMIN = 'STAFF_ADMIN';
    public const CASA  = 'STAFF_CASA';

    private ObjectManager $entityManager;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
            UserFixtures::class,
        ];
    }

    public static function getStaffReferenceName(User $user, Company $company): string
    {
        return 'staff_' . $user->getId() . '_' . $company->getId();
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->entityManager = $manager;

        // Create our main user
        /** @var Company $adminCompany */
        $adminCompany = $this->getReference(CompanyFixtures::KLS);
        /** @var User $admin */
        $admin = $this->getReference(UserFixtures::ADMIN);

        $adminStaff = $this->insertStaff($admin, $adminCompany);
        $this->addReference(self::ADMIN, $adminStaff);
        $this->addStaffReference($adminStaff);

        // We set the user in the tokenStorage to avoid conflict with StaffLogListener
        $this->login($adminStaff);

        // Create a CASA staff
        /** @var Company $casaCompany */
        $casaCompany    = $this->getReference(CompanyFixtures::CASA);
        $casaAdminStaff = $this->insertStaff($admin, $casaCompany);
        $this->addAllCompanyGroupTag($casaAdminStaff);

        /** @var User $managerUser */
        $managerUser      = $this->getReference(UserFixtures::MANAGER);
        $casaManagerStaff = $this->insertStaff($managerUser, $casaCompany);
        $this->addAllCompanyGroupTag($casaAdminStaff);
        $this->addReference(self::CASA, $casaManagerStaff);

        $this->createMultipleStaff($adminCompany, $adminStaff, $admin);
    }

    /**
     * @throws Exception
     */
    private function createMultipleStaff(Company $adminCompany, Staff $adminStaff, User $admin): void
    {
        foreach ($this->getUserData() as $userReference => $datum) {
            /** @var User $user */
            $user    = $this->getReference($userReference);
            $company = $datum['company'] ?? $adminCompany;
            $staff   = $this->createStaffWithCompany($user, $company);

            if (false === \in_array($userReference, [UserFixtures::AUDITOR, UserFixtures::ACCOUNTANT], true)) {
                $staff->setArrangementProjectCreationPermission(true);
            }

            $this->addStaffReference($staff);
            $this->entityManager->persist($staff);
        }

        // Create CA banks staff
        /** @var User $participant */
        $participant = $this->getReference(UserFixtures::PARTICIPANT);

        foreach (CompanyFixtures::CA_SHORTCODE as $companyShortCode) {
            /** @var Company $company */
            $company = $this->getReference('company:' . $companyShortCode);

            if ($company !== $adminCompany) {
                $staff = $this->createStaffWithCompany($participant, $company);
                $this->addAllCompanyGroupTag($staff);
                $this->addStaffReference($staff);
                $this->entityManager->persist($staff);
            }
        }

        $this->entityManager->flush();

        // Create inactive staff
        /** @var User $inactiveUser */
        $inactiveUser = $this->getReference(UserFixtures::INACTIVE);

        foreach ($inactiveUser->getStaff() as $staff) {
            $staff->setCurrentStatus(new StaffStatus($staff, StaffStatus::STATUS_INACTIVE, $adminStaff));
            $this->addRandomCompanyGroupTag($staff);
            $this->entityManager->persist($staff);
        }

        $this->entityManager->flush();

        // Create manyStaff company staff
        /** @var Company $manyStaffCompany */
        $manyStaffCompany = $this->getReference(CompanyFixtures::COMPANY_MANY_STAFF);

        $manyStaffAdminStaff = $this->createStaffWithCompany($admin, $manyStaffCompany);
        $manyStaffAdminStaff->setManager(true);
        $this->addAllCompanyGroupTag($manyStaffAdminStaff);
        $this->entityManager->persist($manyStaffAdminStaff);

        foreach (\range(0, 50) as $i) {
            $user = new User($this->faker->email);
            $this->entityManager->persist($user);

            $staff = $this->createStaffWithCompany($user, $manyStaffCompany);
            $this->addRandomCompanyGroupTag($staff);
            $this->entityManager->persist($staff);
        }

        $this->entityManager->flush();
    }

    private function getUserData(): array
    {
        return [
            UserFixtures::AUDITOR             => [],
            UserFixtures::ACCOUNTANT          => [],
            UserFixtures::OPERATOR            => [],
            UserFixtures::MANAGER             => [],
            UserFixtures::UNITIALIZED         => [],
            UserFixtures::EXTBANK_INITIALIZED => [
                'company' => $this->getReference(CompanyFixtures::COMPANY_EXTERNAL),
            ],
            UserFixtures::EXTBANK_INVITED => [
                'company' => $this->getReference(CompanyFixtures::COMPANY_EXTERNAL),
            ],
            UserFixtures::INACTIVE => [],
        ];
    }

    private function addStaffReference(Staff $staff): void
    {
        $this->addReference(static::getStaffReferenceName($staff->getUser(), $staff->getCompany()), $staff);
    }

    /**
     * @throws Exception
     */
    private function createStaffWithCompany(User $user, ?Company $company = null): Staff
    {
        $company = $company ?? $this->getReference(CompanyFixtures::KLS);

        return new Staff($user, $company->getRootTeam());
    }

    /**
     * Creates a new staff attached to the company.
     */
    private function insertStaff(User $user, Company $company): Staff
    {
        // We need to use SQL since we cannot instantiate Staff entity
        $sql = <<<SQL
                INSERT INTO `core_staff`
                    (id_team, id_user, manager, updated, added, public_id, arrangement_project_creation_permission, agency_project_creation_permission) VALUES 
                    (
                        "{$company->getRootTeam()->getId()}",
                        "{$user->getId()}",
                        1,
                        '2020-01-01', '2020-01-01',
                        "user{$user->getId()}-company{$company->getId()}-staff",
                        1,
                        1
                    )
            SQL;

        $this->entityManager->getConnection()->exec($sql);

        $staffId         = $this->entityManager->getConnection()->lastInsertId();
        $staffActiveCode = StaffStatus::STATUS_ACTIVE;
        $publicId        = \uniqid();

        $sql = <<<SQL
                INSERT INTO core_staff_status
                (id_staff, added_by, status, added, public_id) VALUES 
                ({$staffId}, {$staffId}, {$staffActiveCode}, NOW(), '{$publicId}')
            SQL;

        $this->entityManager->getConnection()->exec($sql);

        $staffStatusId = $this->entityManager->getConnection()->lastInsertId();

        $sql = "UPDATE core_staff SET id_current_status = {$staffStatusId} WHERE id = {$staffId}";
        $this->entityManager->getConnection()->exec($sql);

        if ($company->getCompanyGroup()) {
            $sql = "INSERT INTO core_staff_company_group_tag SELECT {$staffId}, id FROM core_company_group_tag WHERE id_company_group = {$company->getCompanyGroup()->getId()}";
            $this->entityManager->getConnection()->exec($sql);
        }

        return $this->entityManager->getReference(Staff::class, $staffId);
    }

    private function addRandomCompanyGroupTag(Staff $staff): void
    {
        $companyGroupTags = $staff->getCompany()->getCompanyGroupTags();

        foreach ($companyGroupTags as $companyGroupTag) {
            if (1 === $this->faker->randomNumber() % 2) {
                $staff->addCompanyGroupTag($companyGroupTag);
            }
        }
    }

    private function addAllCompanyGroupTag(Staff $staff): void
    {
        $companyGroupTags = $staff->getCompany()->getCompanyGroupTags();

        foreach ($companyGroupTags as $companyGroupTag) {
            $staff->addCompanyGroupTag($companyGroupTag);
        }
    }
}
