<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramContact;

class ProgramContactFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            $this->buildProgramContacts($program, $manager);
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

    /**
     * @throws Exception
     */
    private function buildProgramContacts(Program $program, ObjectManager $manager): void
    {
        $workingScope = ['Aide à la réservation', 'Eligibilité et process', 'Gestion des recouvrements et des appels en garantie', 'Reporting', 'Bagage commercial'];
        for ($i = 1; $i <= \random_int(1, 5); ++$i) {
            $firstName = $this->faker->firstName;
            $lastName  = $this->faker->lastName;
            $email     = \transliterator_transliterate(
                'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII',
                $firstName . '.' . $lastName . '@' . $this->faker->domainName
            );
            $programContact = new ProgramContact(
                $program,
                $firstName,
                $lastName,
                $workingScope[\array_rand($workingScope)],
                \mb_strtolower($email),
                $this->faker->phoneNumber
            );
            $manager->persist($programContact);
        }
    }
}
