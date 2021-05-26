<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\CompanyFixtures;
use Unilend\Core\Entity\Constant\CARegionalBank;
use Unilend\Core\Traits\ConstantsAwareTrait;
use Unilend\CreditGuaranty\Entity\Participation;
use Unilend\CreditGuaranty\Entity\Program;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use ConstantsAwareTrait;

    public const PARTICIPANT_TOUL = 'CG_PARTICIPANT_TOUL';
    public const PARTICIPANT_SAVO = 'CG_PARTICIPANT_SAVO';

    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            $CARegionalBanks = CARegionalBank::REGIONAL_BANKS;
            shuffle($CARegionalBanks);
            foreach ($this->getParticipants() as $participant) {
                $participation = new Participation(
                    $program,
                    $this->getReference(CompanyFixtures::REFERENCE_PREFIX . str_replace('CG_PARTICIPANT_', '', $participant)),
                    (string) $this->faker->randomFloat(2, 0, 1)
                );
                $manager->persist($participation);
                $this->setReference($participant, $participation);
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

    private function getParticipants(): array
    {
        return self::getConstants('PARTICIPANT_');
    }
}
