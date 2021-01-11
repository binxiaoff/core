<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Agency\Entity\Project;
use Unilend\Core\DataFixtures\{AbstractFixtures, StaffFixtures};
use Unilend\Core\Entity\Staff;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const BASIC_PROJECT = 'BASIC_PROJECT';

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $staff */
        $staff   = $this->getReference(StaffFixtures::ADMIN);
        $project = (new Project($staff));

        $manager->persist($project);

        $this->addReference(self::BASIC_PROJECT, $project);

        $manager->flush();
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
