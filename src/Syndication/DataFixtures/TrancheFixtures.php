<?php

namespace Unilend\Syndication\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\{AbstractFixtures, CompanyFixtures};
use Unilend\Core\Entity\Embeddable\LendingRate;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Entity\{Project, Tranche};

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
                    new Money('EUR', 1000000 * count(CompanyFixtures::COMPANIES)),
                    "Tranche {$letters[$i]}",
                    $this->faker->randomDigit,
                    'constant_capital',
                    'stand_by',
                    $this->faker->hexColor
                ))
                ->setDuration(1)
                ->setRate(new LendingRate('EONIA', '0.0200', null, 'none'))
                ->setUnsyndicatedFunderType($i === 2 ? Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER : null)
                ->setSyndicated($i !== 2);
                $this->forcePublicId($tranche, "tranche-{$project->getPublicId()}-{$i}");
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
