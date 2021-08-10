<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Constant\CARegionalBank;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\CreditGuaranty\Entity\Participation;
use KLS\CreditGuaranty\Entity\Program;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use ConstantsAwareTrait;

    public const PARTICIPANT_TOUL = 'CG_PARTICIPANT_TOUL';
    public const PARTICIPANT_SAVO = 'CG_PARTICIPANT_SAVO';

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [ProgramFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            $CARegionalBanks = CARegionalBank::REGIONAL_BANKS;
            \shuffle($CARegionalBanks);

            foreach ($this->getParticipantReferences() as $participantReference) {
                /** @var Company $company */
                $company       = $this->getReference(CompanyFixtures::REFERENCE_PREFIX . \str_replace('CG_PARTICIPANT_', '', $participantReference));
                $participation = new Participation(
                    $program,
                    $company,
                    (string) $this->faker->randomFloat(2, 0, 1)
                );

                $manager->persist($participation);
                $this->setReference($participantReference, $participation);
            }
        }
        $manager->flush();
    }

    private function getParticipantReferences(): array
    {
        return self::getConstants('PARTICIPANT_');
    }
}
