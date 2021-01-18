<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Agency\Entity\Borrower;
use Unilend\Agency\Entity\Contact;
use Unilend\Agency\Entity\Project;
use Unilend\Core\DataFixtures\{AbstractFixtures, StaffFixtures};
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Staff;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const BASIC_PROJECT = 'BASIC_PROJECT';

    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $staff */
        $staff   = $this->getReference(StaffFixtures::ADMIN);
        $project = new Project($staff);
        $this->manager = $manager;

        $manager->persist($project);

        $this->createContacts($project, $staff);

        foreach (range(0, 3) as $i) {
            $manager->persist($this->createBorrower($project, $staff));
        }
        $manager->flush();
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws Exception
     */
    public function createContacts(Project $project, Staff $staff): void
    {
        foreach (Contact::getTypes() as $type) {
            for ($i = 0; $i < 2; $i++) {
                $contact = new Contact(
                    $project,
                    $type,
                    $staff,
                    $this->faker->firstName,
                    $this->faker->lastName,
                    $staff->getCompany()->getDisplayName(),
                    $this->faker->jobTitle,
                    $this->faker->email,
                    '+33600000000',
                    $i === 0
                );

                $this->manager->persist($contact);
            }
        }
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return Borrower
     */
    public function createBorrower(Project $project, Staff $staff)
    {
        $siren = $this->generateSiren();
        $city = $this->faker->city;

        return new Borrower(
            $project,
            $staff,
            $this->faker->company,
            'SARL',
            new Money((string) $this->faker->randomFloat(0, 100000), 'EUR'),
            $this->faker->address,
            implode(' ', ['RCS', strtoupper($city), $this->faker->randomDigit % 2 ? 'A' : 'B', $siren]),
            $city,
            $siren,
            $this->faker->firstName,
            $this->faker->lastName,
            $this->faker->email,
            $this->faker->firstName,
            $this->faker->lastName,
            $this->faker->email,
        );
    }

    /**
     * @return string
     */
    public function generateSiren()
    {
        // A siren use the Luhn algorithm to validate. Its final lenght (number + checksum must be 9)
        // https://fr.wikipedia.org/wiki/Luhn_algorithm
        // https://fr.wikipedia.org/wiki/Syst%C3%A8me_d%27identification_du_r%C3%A9pertoire_des_entreprises#Calcul_et_validit%C3%A9_d'un_num%C3%A9ro_SIREN
        $siren = $this->faker->randomNumber(8); // First we generate a 8 digit long number
        $siren = str_split((string) $siren); // Conversion and split into an array
        $checksum = array_map(static fn ($i, $d) => 1 === $i % 2 ? array_sum(str_split((string) ($d * 2))) : $d, range(0, 7), $siren); // Double each odd index digit
        $checksum = array_sum($checksum); // Sum the resulting array
        $checksum *= 9; // Multiply it by 9
        $checksum %= 10; // Checksum is the last digit of the sum

        return implode('', [...$siren, $checksum]);
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
        ];
    }
}
