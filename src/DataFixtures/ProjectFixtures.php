<?php

namespace Unilend\DataFixtures;

use DateTimeImmutable;
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
    public const PROJECT_DRAFT = 'PROJECT_DRAFT';
    public const PROJECT_DRAFT_PARTICIPATION = 'PROJECT_DRAFT_PARTICIPATION';
    public const PROJECTS = [
        self::PROJECT_ALLOCATION,
        self::PROJECT_REPLY,
        self::PROJECT_DRAFT,
        self::PROJECT_DRAFT_PARTICIPATION,
    ];
    public const PROJECTS_WITH_PARTICIPATION = [
        self::PROJECT_ALLOCATION,
        self::PROJECT_REPLY,
        self::PROJECT_DRAFT_PARTICIPATION,
    ];
    public const PROJECTS_WITH_PARTICIPATION_TRANCHES = [
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
        $projectDraft = $this->createProject('Project created', ProjectStatus::STATUS_DRAFT);
        $projectDraftParticipation = $this->createProject('Project draft', ProjectStatus::STATUS_DRAFT);
        $manager->persist($projectDraft);
        $manager->persist($projectAllocation);
        $manager->persist($projectReply);
        $manager->persist($projectDraftParticipation);
        $this->addReference(self::PROJECT_ALLOCATION, $projectAllocation);
        $this->addReference(self::PROJECT_REPLY, $projectReply);
        $this->addReference(self::PROJECT_DRAFT, $projectDraft);
        $this->addReference(self::PROJECT_DRAFT_PARTICIPATION, $projectDraftParticipation);
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
            ->setParticipantReplyDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+70 days', '+1 year')))
            ->setAllocationDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+1 year', '+2 year')))
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
