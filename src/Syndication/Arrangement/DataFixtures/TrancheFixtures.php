<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\Entity\Constant\LoanType;
use KLS\Core\Entity\Embeddable\LendingRate;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\Tranche;
use ReflectionException;

class TrancheFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS);
        $letters  = 'ABCDEFGH';
        foreach ($projects as $project) {
            for ($i = 0; $i <= 4; ++$i) {
                $tranche = (new Tranche(
                    $project,
                    new Money('EUR', (string) (1000000 * \count(CompanyFixtures::COMPANIES))),
                    "Tranche {$letters[$i]}",
                    $this->faker->randomDigit,
                    'constant_capital',
                    LoanType::STAND_BY,
                    $this->faker->hexColor
                ))
                    ->setDuration(1)
                    ->setRate(new LendingRate('EONIA', '0.0200', null, 'none'))
                    ->setUnsyndicatedFunderType(2 === $i ? Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER : null)
                    ->setSyndicated(2 !== $i)
                ;
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
