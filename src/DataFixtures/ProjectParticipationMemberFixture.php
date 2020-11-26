<?php

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\Entity\Staff;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipationMember;

class ProjectParticipationMemberFixture extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    public static int $id = 0; // Auto increment public ids

    /**
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Staff $adminStaff */
        $adminStaff = $this->getReference(StaffFixtures::ADMIN);
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS);
        foreach ($projects as $project) {
            foreach ($project->getProjectParticipations() as $participation) {
                foreach ($participation->getParticipant()->getStaff() as $staff) {
                    if ($staff !== $adminStaff && $this->faker->boolean) {
                        $member = new ProjectParticipationMember(
                            $participation,
                            $staff,
                            $adminStaff
                        );
                        $manager->persist($member);
                    }
                }
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
            ProjectParticipationFixtures::class,
        ];
    }
}
