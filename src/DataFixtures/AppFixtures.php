<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\ProjectStatus;

class AppFixtures extends Fixture implements FixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $generator = new FixtureGenerator($manager, $faker);

        // Fake marker segments
        $manager->getConnection()->exec('ALTER TABLE market_segment AUTO_INCREMENT = 1;'); // We need to reset auto increment cause ID are important for the front :(
        for ($i = 0; $i < 10; $i++) {
            $marketSegments[] = $generator->marketSegment("Segment #$i");
        }
        // Fake companies
        for ($i = 1; $i <= 5; $i++) {
            $companies[] = $generator->company("Company $i");
        }

        // Our main user
        $client = $generator->user('admin@ca-lendingservices.com');
        $staff = $client->getCurrentStaff()->setMarketSegments($marketSegments);

        // Fake project at the allocation phase
        $project = $generator->project('Project allocation', ProjectStatus::STATUS_ALLOCATION, $staff, $marketSegments[0]);
        $tranches = [];
        for ($i = 1; $i <= 5; $i++) {
            $tranches[] = $generator->tranche($project, "Tranche $i", $i * 1000000);
        }
        $generator->participation($project, $staff->getCompany(), $staff);
        foreach ($companies as $company) {
            $participation = $generator->participation($project, $company, $staff);
            foreach ($tranches as $tranche) {
                $generator->participationTranche($participation, $tranche, $staff, 1000000, 1000000);
            }
        }

        // Fake project before the allocation phase
        $project = $generator->project('Project reply', ProjectStatus::STATUS_PARTICIPANT_REPLY, $staff, $marketSegments[1]);
        $tranches = [];
        for ($i = 1; $i <= 2; $i++) {
            $tranches[] = $generator->tranche($project, "Tranche $i", $i * 1000000);
        }
        $generator->participation($project, $staff->getCompany(), $staff);
        foreach ($companies as $company) {
            $participation = $generator->participation($project, $company, $staff);
            foreach ($tranches as $tranche) {
                $generator->participationTranche($participation, $tranche, $staff, 1000000, 1000000);
            }
        }

        // Flush all data
        $manager->flush();
    }
}
