<?php

namespace Unilend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Entity\Clients;
use Unilend\Entity\Company;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;

class StaffFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    public const ADMIN = 'STAFF_ADMIN';

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
        /** @var Clients $admin */
        $admin = $this->getReference(UserFixtures::ADMIN);
        $adminStaff = $this->insertStaff($admin, $adminCompany, $manager, [Staff::DUTY_STAFF_ADMIN], MarketSegmentFixtures::SEGMENTS);

        $this->addReference(self::ADMIN, $adminStaff);
        $this->addStaffReference($adminStaff);

        // We set the user in the tokenStorage to avoid conflict with StaffLogListener
        $this->login($adminStaff);

        $data = [
            UserFixtures::AUDITOR => [
                'roles' => [Staff::DUTY_STAFF_AUDITOR],
            ],
            UserFixtures::ACCOUNTANT => [
                'roles' => [Staff::DUTY_STAFF_ACCOUNTANT],
            ],
            UserFixtures::OPERATOR => [],
            UserFixtures::MANAGER => [
                'roles' => [Staff::DUTY_STAFF_MANAGER],
                'marketSegments' => new ArrayCollection(),
            ],
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
            /** @var Clients $user */
            $user = $this->getReference($userReference);
            $company = $datum['company'] ?? $adminCompany;
            $staff = $this->createStaff($user, $company, $datum['roles'] ?? null, $datum['marketSegments'] ?? null);
            $this->addStaffReference($staff);
            $manager->persist($staff);
        }

        // Attach other companies to the other user
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var Clients $participant */
        $participant = $this->getReference(UserFixtures::PARTICIPANT);
        foreach ($companies as $company) {
            if ($company !== $adminCompany) {
                $staff = $this->createStaff($participant, $company, [Staff::DUTY_STAFF_ADMIN]);
                $this->addStaffReference($staff);
                $manager->persist($staff);
            }
        }

        $manager->flush();

        /** @var Clients $inactiveUser */
        $inactiveUser = $this->getReference(UserFixtures::INACTIVE);

        foreach ($inactiveUser->getStaff() as $staff) {
            $staff->setCurrentStatus(new StaffStatus($staff, StaffStatus::STATUS_INACTIVE, $adminStaff));
            $manager->persist($staff);
        }

        $manager->flush();

        /** @var Company $manyStaffCompany */
        $manyStaffCompany = $this->getReference(CompanyFixtures::COMPANY_MANY_STAFF);

        $manyStaffAdminStaff = $this->createStaff($admin, $manyStaffCompany, [Staff::DUTY_STAFF_ADMIN]);
        $manager->persist($manyStaffAdminStaff);

        foreach (range(0, 50) as $i) {
            $user = new Clients($this->faker->email);
            $manager->persist($user);
            $manager->persist($this->createStaff($user, $manyStaffCompany, [Staff::DUTY_STAFF_OPERATOR], null, $manyStaffAdminStaff));
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            MarketSegmentFixtures::class,
            CompanyFixtures::class,
            UserFixtures::class,
        ];
    }

    /**
     * @param Clients $client
     * @param Company $company
     *
     * @return string
     */
    public static function getStaffReferenceName(Clients $client, Company $company)
    {
        return 'staff_' . $client->getId() . '_' . $company->getId();
    }

    /**
     * Creates a new staff attached to the company
     *
     * @param Clients       $user
     * @param Company       $company
     * @param ObjectManager $manager
     * @param array         $roles
     *
     * @param array         $marketSegments
     *
     * @return Staff
     *
     * @throws \JsonException
     */
    private function insertStaff(Clients $user, Company $company, ObjectManager $manager, array $roles = [], array $marketSegments = []): Staff
    {
        // We need to use SQL since we cannot instantiate Staff entity
        $rolesEncoded = json_encode($roles, JSON_THROW_ON_ERROR);
        $sql = <<<SQL
            INSERT INTO `staff` 
                (id_company, id_client, roles, updated, added, public_id) VALUES 
                (
                    "{$company->getId()}", 
                    "{$user->getId()}", 
                    '{$rolesEncoded}', 
                    '2020-01-01', '2020-01-01', 
                    "client{$user->getId()}-company{$company->getId()}-staff"
                )
        SQL;
        $manager->getConnection()->exec($sql);
        $staffId = $manager->getConnection()->lastInsertId();

        $staffActiveCode = StaffStatus::STATUS_ACTIVE;
        $publicId = uniqid();
        $sql = <<<SQL
            INSERT INTO staff_status
            (id_staff, added_by, status, added, public_id) VALUES 
            ({$staffId}, {$staffId}, {$staffActiveCode}, NOW(), '{$publicId}')
SQL;

        $manager->getConnection()->exec($sql);
        $staffStatusId = $manager->getConnection()->lastInsertId();

        $sql = "UPDATE staff SET id_current_status = {$staffStatusId} WHERE id = {$staffId}";
        $manager->getConnection()->exec($sql);

        foreach ($marketSegments as $marketSegment) {
            if (\is_string($marketSegment)) {
                $marketSegment = $this->getReference($marketSegment);
            }
            $marketSegmentId = $marketSegment->getId();

            $sql = "INSERT INTO staff_market_segment(staff_id, market_segment_id) VALUES ({$staffId}, {$marketSegmentId})";
            $manager->getConnection()->exec($sql);
        }

        /** @var Staff $staff */

        return $manager->getReference(Staff::class, $staffId);
    }

    /**
     * @param Clients              $user
     * @param Company|null         $company
     * @param array|null           $roles
     * @param ArrayCollection|null $markerSegments
     *
     * @param Staff|null           $addedBy
     *
     * @return Staff
     *
     * @throws Exception
     */
    private function createStaff(
        Clients $user,
        ?Company $company = null,
        ?array $roles = [Staff::DUTY_STAFF_OPERATOR],
        ?ArrayCollection $markerSegments = null,
        ?Staff $addedBy = null
    ): Staff {
        $company = $company ?? $this->getReference(CompanyFixtures::CALS);
        $addedBy = $addedBy ?? $this->getReference(self::ADMIN);
        $roles = $roles ?? [Staff::DUTY_STAFF_OPERATOR];
        $markerSegments = $markerSegments ?? new ArrayCollection($this->getReferences(MarketSegmentFixtures::SEGMENTS));
        $staff = new Staff($company, $user, $addedBy);
        $staff->setRoles($roles);
        $staff->setMarketSegments($markerSegments);

        return $staff;
    }

    /**
     * @param Staff $staff
     */
    private function addStaffReference(Staff $staff)
    {
        $this->addReference(static::getStaffReferenceName($staff->getClient(), $staff->getCompany()), $staff);
    }
}
