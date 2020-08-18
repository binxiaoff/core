<?php

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipationMember;

class ProjectParticipationMemberFixture extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    public static $id = 0; // Auto increment public ids

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS);
        foreach ($projects as $project) {
            foreach ($project->getProjectParticipations() as $participation) {
                $member = new ProjectParticipationMember(
                    $participation,
                    $participation->getParticipant()->getStaff()[0],
                    $project->getProjectParticipations()[0]->getParticipant()->getStaff()[0]
                );
                $manager->persist($member);
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
            ParticipationFixtures::class,
        ];
    }
}
