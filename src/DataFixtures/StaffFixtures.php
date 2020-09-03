<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;
use Unilend\Entity\Company;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;

class StaffFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    public const ADMIN = 'STAFF_ADMIN';

    /**
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        // Create our main user
        /** @var Company $company */
        $adminCompany = $this->getReference(CompanyFixtures::CALS);
        /** @var Clients $user */
        $user = $this->getReference(UserFixtures::ADMIN);
        $adminStaff = $this->createStaff($user, $adminCompany, $manager, [Staff::DUTY_STAFF_ADMIN]);
        // We set the user in the tokenStorage to avoid conflict with StaffLogListener
        $this->login($user);
        $manager->persist($adminStaff);
        $manager->persist($adminStaff->getCurrentStatus());

        $auditor = $this->getReference(UserFixtures::AUDITOR);
        $auditorStaff = $this->createStaff($auditor, $adminCompany, $manager, [Staff::DUTY_STAFF_AUDITOR, Staff::DUTY_STAFF_ACCOUNTANT]);
        $manager->persist($auditorStaff);
        $manager->persist($auditorStaff->getCurrentStatus());

        $invited = $this->getReference(UserFixtures::INVITED);
        $invitedStaff = $this->createStaff($invited, $adminCompany, $manager, [Staff::DUTY_STAFF_OPERATOR]);
        $manager->persist($invitedStaff);
        $manager->persist($invitedStaff->getCurrentStatus());

        $userManager = $this->getReference(UserFixtures::MANAGER);
        $managerStaff = $this->createStaff($userManager, $adminCompany, $manager, [Staff::DUTY_STAFF_MANAGER]);
        $manager->persist($managerStaff);
        $manager->persist($managerStaff->getCurrentStatus());

        // Attach other companies to the other user
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var Clients $user */
        $other = $this->getReference(UserFixtures::PARTICIPANT);
        foreach ($companies as $company) {
            if ($company !== $adminCompany) {
                $staff = $this->createStaff($other, $company, $manager, [Staff::DUTY_STAFF_ADMIN]);
                $manager->persist($staff);
                $manager->persist($staff->getCurrentStatus());
            }
        }

        $manager->flush();
        $this->addReference(self::ADMIN, $adminStaff);
    }

    /**
     * Creates a new staff attached to the company
     *
     * @param Clients $user
     * @param Company $company
     * @param ObjectManager $manager
     *
     * @return Staff
     *
     * @throws \Exception
     */
    public function createStaff(Clients $user, Company $company, ObjectManager $manager, array $roles): Staff
    {
        // We need to use SQL since we cannot instanciate Staff entity
        $rolesEncoded = json_encode($roles);
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
        /** @var Staff $staff */
        $staff = $manager->getReference(Staff::class, $staffId);
        $staffStatus = new StaffStatus($staff, StaffStatus::STATUS_ACTIVE, $staff);
        $staff->setCurrentStatus($staffStatus);
        $user->setCurrentStaff($staff);
        $staff->setMarketSegments(array_map(function (string $segment) {
            return $this->getReference($segment);
        }, MarketSegmentFixtures::SEGMENTS));

        return $staff;
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
}
