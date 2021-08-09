<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\CreditGuaranty\Entity\Participation;
use KLS\CreditGuaranty\Entity\Program;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use KLS\Test\Core\DataFixtures\Companies\BasicCompanyFixtures;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const PARTICIPANT_BASIC = 'program:participant:foo';

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            BasicCompanyFixtures::class,
            ProgramFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);

        foreach ($this->loadData() as $reference => $participationData) {
            $participation = new Participation($program, $participationData['company'], $participationData['quota']);

            $this->setPublicId($participation, $reference);
            $this->setReference($reference, $participation);

            $manager->persist($participation);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        yield self::PARTICIPANT_BASIC => [
            'company' => $this->getReference('company:basic'),
            'quota'   => (string) (\mt_rand() / \mt_getrandmax()),
        ];
    }
}
