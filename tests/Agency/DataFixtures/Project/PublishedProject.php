<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\DataFixtures\Project;

use Doctrine\Persistence\ObjectManager;

class PublishedProject extends DraftProject
{
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $project = $this->getReference('project:published');

        $this->publishProject($project);

        $manager->persist($project);

        $manager->flush();
    }

    protected function getName(): string
    {
        return 'published';
    }
}
