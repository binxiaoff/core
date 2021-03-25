<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\{AbstractFixtures};
use Unilend\CreditGuaranty\Entity\{Program, ProgramContact};

class ProgramContactFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $programReferenceNames = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferenceNames as $programReferenceName) {
            $program = $this->getReference($programReferenceName);

            $this->buildProgramContacts($program, $manager);
        }
        $manager->flush();
    }

    /**
     * @param Program       $program
     * @param ObjectManager $manager
     */
    public function buildProgramContacts(Program $program, ObjectManager $manager)
    {
        $workingScope = ['Aide à la réservation', 'Eligibilité et process', 'Gestion des recouvrements et des appels en garantie', 'Reporting', 'Bagage commercial'];
        for ($i = 1; $i <= rand(1, 5); $i++) {
            $firstName = $this->faker->firstName;
            $lastName = $this->faker->lastName;
            $email = transliterator_transliterate('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII', $firstName . '.' . $lastName . '@' . $this->faker->domainName);
            $programContact = new ProgramContact(
                $program,
                $firstName,
                $lastName,
                $workingScope[array_rand($workingScope)],
                strtolower($email),
                $this->faker->phoneNumber
            );
            $manager->persist($programContact);
        }
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramFixtures::class,
        ];
    }
}
