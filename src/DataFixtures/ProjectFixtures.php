<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Sluggable\Util\Urlizer;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;
use Unilend\Entity\Company;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    public const PROJECT_ALLOCATION = 'PROJECT_ALLOCATION';
    public const PROJECT_REPLY = 'PROJECT_REPLY';
    public const PROJECTS = [
        self::PROJECT_ALLOCATION,
        self::PROJECT_REPLY,
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $projectAllocation = $this->createProject('Project allocation', ProjectStatus::STATUS_ALLOCATION);
        $projectReply = $this->createProject('Project reply', ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $manager->persist($projectAllocation);
        $manager->persist($projectReply);
        $this->addReference(self::PROJECT_ALLOCATION, $projectAllocation);
        $this->addReference(self::PROJECT_REPLY, $projectReply);
        $manager->flush();
    }

    /**
     * @param string $title
     * @param int    $status
     *
     * @return Project
     *
     * @throws \ReflectionException
     */
    public function createProject(string $title, int $status): Project
    {
        $money = new Money('EUR', '5000000');
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);
        $project = (new Project(
            $staff,
            'RISK-GROUP-1',
            $money,
            $this->getReference(MarketSegmentFixtures::SEGMENT1)
        ))
            ->setTitle($title)
            ->setInternalRatingScore('B')
            ->setFundingSpecificity('FSA')
            ->setParticipationType(Project::PROJECT_PARTICIPATION_TYPE_DIRECT)
            ->setSyndicationType(Project::PROJECT_SYNDICATION_TYPE_PRIMARY)
            ->setInterestExpressionEnabled(false) // "Réponse ferme"
            ->setDescription($this->faker->sentence);
        $status = new ProjectStatus($project, $status, $staff);
        $project->setCurrentStatus($status);
        $this->forcePublicId($project, Urlizer::urlize($title));

        return $project;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
            MarketSegmentFixtures::class,
        ];
    }
}
