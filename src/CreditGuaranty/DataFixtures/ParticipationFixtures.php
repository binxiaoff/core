<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\CompanyFixtures;
use Unilend\Core\Entity\Constant\CARegionalBank;
use Unilend\CreditGuaranty\Entity\Participation;
use Unilend\CreditGuaranty\Entity\Program;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            $CARegionalBanks = CARegionalBank::REGIONAL_BANKS;
            shuffle($CARegionalBanks);
            for ($i = 0; $i <= $this->faker->randomNumber(1); ++$i) {
                $manager->persist(new Participation(
                    $program,
                    $this->getReference(CompanyFixtures::REFERENCE_PREFIX . array_shift($CARegionalBanks)),
                    (string) $this->faker->randomFloat(2, 0, 1)
                ));
            }
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [ProgramFixtures::class];
    }
}
