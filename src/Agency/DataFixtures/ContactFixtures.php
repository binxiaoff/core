<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Agency\Entity\{Contact, Project};
use Unilend\Core\DataFixtures\{AbstractFixtures, StaffFixtures};
use Unilend\Core\Entity\Staff;

class ContactFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $staff */
        $staff   = $this->getReference(StaffFixtures::ADMIN);
        /** @var Project $project */
        $project = $this->getReference(ProjectFixtures::BASIC_PROJECT);

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

                $manager->persist($contact);
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
            StaffFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
