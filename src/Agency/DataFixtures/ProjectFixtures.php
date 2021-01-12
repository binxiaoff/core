<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Agency\Entity\Contact;
use Unilend\Agency\Entity\Project;
use Unilend\Core\DataFixtures\{AbstractFixtures, StaffFixtures};
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
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $staff */
        $staff   = $this->getReference(StaffFixtures::ADMIN);
        $project = new Project($staff);
        $this->manager = $manager;

        $manager->persist($project);

        $this->createContacts($project, $staff);

        $manager->flush();
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws \Exception
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
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
        ];
    }
}
