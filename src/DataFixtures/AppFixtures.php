<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Tranche;

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

        // Test new user
        $generator->user('new@ca-lendingservices.com', [$companies[0]]);

        // Test arraganger
        $client = $generator->user('admin@ca-lendingservices.com', [$generator->company('KLS Company', 'CALS')], 'arranger');
        $staff = $client->getCurrentStaff()->setMarketSegments($marketSegments);

        // Fake project at the allocation phase
        $project = $generator->project('Project allocation', ProjectStatus::STATUS_ALLOCATION, $staff, $marketSegments[0]);
        /** @var Tranche[] $tranches */
        $tranches = [];
        $letters = 'ABCDEFGH';
        for ($i = 1; $i <= 5; $i++) {
            $tranches[] = $generator->tranche($project, $letters[$i], 5000000);
        }
        // Tranche 3 will be not syndicated
        $staffParticipation = $generator->participation($project, $staff->getCompany(), $staff);
        $tranches[3]->setSyndicated(0);
        $generator->participationTranche($staffParticipation, $tranches[3], $staff, 1000000, 1000000);
        // Everyone participates to other tranches
        foreach ($companies as $company) {
            $participation = $generator->participation($project, $company, $staff);
            foreach ($tranches as $k => $tranche) {
                if (3 !== $k) {
                    $generator->participationTranche($participation, $tranche, $staff, 1000000, 1000000);
                }
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
