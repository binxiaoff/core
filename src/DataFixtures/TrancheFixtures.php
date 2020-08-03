<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Sluggable\Util\Urlizer;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;
use Unilend\Entity\Company;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;
use Unilend\Entity\Tranche;

class TrancheFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS);
        $letters = 'ABCDEFGH';
        foreach ($projects as $project) {
            for ($i = 0; $i <= 4; $i++) {
                $tranche = (new Tranche(
                    $project,
                    new Money('EUR', '5000000'),
                    $letters[$i],
                    $this->faker->randomDigit,
                    'constant_capital',
                    'stand_by',
                    $this->faker->hexColor
                ))
                ->setSyndicated($i !== 2);
                $project->addTranche($tranche);
                $manager->persist($tranche);
            }
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
        ];
    }
}
