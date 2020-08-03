<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
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
        /** @var Company $company */
        $company = $this->getReference(CompanyFixtures::CALS);
        /** @var Clients $user */
        $user = $this->getReference(UserFixtures::ADMIN);
        // We need to use SQL since we cannot instanciate Staff entity
        $sql = <<<SQL
            INSERT INTO `staff` 
                (id_company, id_client, roles, updated, added, public_id) VALUES 
                (
                    "{$company->getId()}", 
                    "{$user->getId()}", 
                    '[\"DUTY_STAFF_ADMIN\"]', 
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
        $manager->persist($staff);
        $manager->persist($staff->getCurrentStatus());
        $manager->flush();
        $this->addReference(self::ADMIN, $staff);
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
