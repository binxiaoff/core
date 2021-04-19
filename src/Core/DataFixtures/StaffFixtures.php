<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use JsonException;
use Unilend\Core\Entity\{Company, Staff, StaffStatus, User};

class StaffFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const ADMIN = 'STAFF_ADMIN';
    public const CASA  = 'STAFF_CASA';

    /**
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        // Create our main user
        /** @var Company $adminCompany */
        $adminCompany = $this->getReference(CompanyFixtures::CALS);
        /** @var User $admin */
        $admin = $this->getReference(UserFixtures::ADMIN);
        $adminStaff = $this->insertStaff($admin, $adminCompany, $manager, []);

        $this->addReference(self::ADMIN, $adminStaff);
        $this->addStaffReference($adminStaff);

        // We set the user in the tokenStorage to avoid conflict with StaffLogListener
        $this->login($adminStaff);

        // Create a CASA staff
        /** @var Company $casaCompany */
        $casaCompany    = $this->getReference(CompanyFixtures::CASA);
        $casaAdminStaff = $this->insertStaff($admin, $casaCompany, $manager);
        $this->addAllCompanyGroupTag($casaAdminStaff);

        /** @var User $managerUser */
        $managerUser      = $this->getReference(UserFixtures::MANAGER);
        $casaManagerStaff = $this->insertStaff($managerUser, $casaCompany, $manager);
        $this->addAllCompanyGroupTag($casaAdminStaff);
        $this->addReference(self::CASA, $casaManagerStaff);

        $data = [
            UserFixtures::AUDITOR => [
            ],
            UserFixtures::ACCOUNTANT => [
            ],
            UserFixtures::OPERATOR => [],
            UserFixtures::MANAGER => [],
            UserFixtures::UNITIALIZED => [],
            UserFixtures::EXTBANK_INITIALIZED => [
                'company' => $this->getReference(CompanyFixtures::COMPANY_EXTERNAL),
            ],
            UserFixtures::EXTBANK_INVITED => [
                'company' => $this->getReference(CompanyFixtures::COMPANY_EXTERNAL),
            ],
            UserFixtures::INACTIVE => [],
        ];

        foreach ($data as $userReference => $datum) {
            /** @var User $user */
            $user = $this->getReference($userReference);
            $company = $datum['company'] ?? $adminCompany;
            $staff = $this->createStaff($user, $company);
            if (false === \in_array($userReference, [UserFixtures::AUDITOR, UserFixtures::ACCOUNTANT], true)) {
                $staff->setArrangementProjectCreationPermission(true);
            }
            $this->addStaffReference($staff);
            $manager->persist($staff);
        }

        // Attach other companies to the other user
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var User $participant */
        $participant = $this->getReference(UserFixtures::PARTICIPANT);
        foreach ($companies as $company) {
            if ($company !== $adminCompany) {
                $staff = $this->createStaff($participant, $company);
                $this->addStaffReference($staff);
                $manager->persist($staff);
            }
        }

        $manager->flush();

        /** @var User $inactiveUser */
        $inactiveUser = $this->getReference(UserFixtures::INACTIVE);

        foreach ($inactiveUser->getStaff() as $staff) {
            $staff->setCurrentStatus(new StaffStatus($staff, StaffStatus::STATUS_INACTIVE, $adminStaff));
            $this->addRandomCompanyGroupTag($staff);
            $manager->persist($staff);
        }

        $manager->flush();

        /** @var Company $manyStaffCompany */
        $manyStaffCompany = $this->getReference(CompanyFixtures::COMPANY_MANY_STAFF);

        $manyStaffAdminStaff = $this->createStaff($admin, $manyStaffCompany);
        $manyStaffAdminStaff->setManager(true);
        $this->addAllCompanyGroupTag($manyStaffAdminStaff);
        $manager->persist($manyStaffAdminStaff);

        foreach (range(0, 50) as $i) {
            $user = new User($this->faker->email);
            $manager->persist($user);
            $staff = $this->createStaff($user, $manyStaffCompany);
            $this->addRandomCompanyGroupTag($staff);
            $manager->persist($staff);
        }

        $manager->flush();
    }

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

    /**
     * @param User    $user
     * @param Company $company
     *
     * @return string
     */
    public static function getStaffReferenceName(User $user, Company $company)
    {
        return 'staff_' . $user->getId() . '_' . $company->getId();
    }

    /**
     * Creates a new staff attached to the company
     *
     * @param User          $user
     * @param Company       $company
     * @param ObjectManager $manager
     * @param array         $roles
     *
     * @return Staff
     *
     * @throws JsonException
     */
    private function insertStaff(User $user, Company $company, ObjectManager $manager, array $roles = []): Staff
    {
        // We need to use SQL since we cannot instantiate Staff entity
        $rolesEncoded = json_encode($roles, JSON_THROW_ON_ERROR);
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
        $manager->getConnection()->exec($sql);
        $staffId = $manager->getConnection()->lastInsertId();

        $staffActiveCode = StaffStatus::STATUS_ACTIVE;
        $publicId = uniqid();
        $sql = <<<SQL
            INSERT INTO core_staff_status
            (id_staff, added_by, status, added, public_id) VALUES 
            ({$staffId}, {$staffId}, {$staffActiveCode}, NOW(), '{$publicId}')
SQL;

        $manager->getConnection()->exec($sql);
        $staffStatusId = $manager->getConnection()->lastInsertId();

        $sql = "UPDATE core_staff SET id_current_status = {$staffStatusId} WHERE id = {$staffId}";
        $manager->getConnection()->exec($sql);

        if ($company->getCompanyGroup()) {
            $sql = "INSERT INTO core_staff_company_group_tag SELECT {$staffId}, id FROM core_company_group_tag WHERE id_company_group = {$company->getCompanyGroup()->getId()}";
            $manager->getConnection()->exec($sql);
        }
        /** @var Staff $staff */

        return $manager->getReference(Staff::class, $staffId);
    }

    /**
     * @param User         $user
     * @param Company|null $company
     *
     * @return Staff
     *
     * @throws Exception
     */
    private function createStaff(
        User $user,
        ?Company $company = null
    ): Staff {
        $company = $company ?? $this->getReference(CompanyFixtures::CALS);

        return new Staff($user, $company->getRootTeam());
    }

    /**
     * @param Staff $staff
     */
    private function addRandomCompanyGroupTag(Staff $staff)
    {
        $companyGroupTags = $staff->getCompany()->getCompanyGroupTags();

        foreach ($companyGroupTags as $companyGroupTag) {
            if (1 === $this->faker->randomNumber() % 2) {
                $staff->addCompanyGroupTag($companyGroupTag);
            }
        }
    }

    /**
     * @param Staff $staff
     */
    private function addAllCompanyGroupTag(Staff $staff)
    {
        $companyGroupTags = $staff->getCompany()->getCompanyGroupTags();

        foreach ($companyGroupTags as $companyGroupTag) {
            $staff->addCompanyGroupTag($companyGroupTag);
        }
    }

    /**
     * @param Staff $staff
     */
    private function addStaffReference(Staff $staff)
    {
        $this->addReference(static::getStaffReferenceName($staff->getUser(), $staff->getCompany()), $staff);
    }
}
