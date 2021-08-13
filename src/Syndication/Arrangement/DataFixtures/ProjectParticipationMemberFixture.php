<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\Staff;
use KLS\Core\Model\Bitmask;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;

class ProjectParticipationMemberFixture extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    public static int $id = 0; // Auto increment public ids

    /**
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

                        if ($participation->getParticipant() !== $project->getArranger()) {
                            $member->setPermissions(new Bitmask(ProjectParticipationMember::PERMISSION_WRITE));
                        }

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
