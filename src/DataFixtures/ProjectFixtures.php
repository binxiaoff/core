<?php

namespace Unilend\DataFixtures;

use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionException;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;
use Unilend\Entity\Company;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\File;
use Unilend\Entity\FileVersion;
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
    public const PROJECT_FINISHED = 'PROJECT_FINISHED';
    public const PROJECT_ARCHIVED = 'PROJECT_ARCHIVED';
    public const PROJECT_OTHER_USER = 'PROJECT_OTHER_USER';
    public const PROJECTS = [
        self::PROJECT_ALLOCATION,
        self::PROJECT_REPLY,
        self::PROJECT_DRAFT,
        self::PROJECT_DRAFT_PARTICIPATION,
        self::PROJECT_FINISHED,
        self::PROJECT_ARCHIVED,
    ];
    public const PROJECTS_WITH_PARTICIPATION = [
        self::PROJECT_ALLOCATION,
        self::PROJECT_REPLY,
        self::PROJECT_DRAFT_PARTICIPATION,
        self::PROJECT_FINISHED,
        self::PROJECT_ARCHIVED,
    ];
    public const PROJECTS_WITH_PARTICIPATION_TRANCHES = [
        self::PROJECT_ALLOCATION,
        self::PROJECT_REPLY,
        self::PROJECT_FINISHED,
    ];
    public const PREVIOUS_STATUSES = [
        ProjectStatus::STATUS_PARTICIPANT_REPLY => [ProjectStatus::STATUS_DRAFT],
        ProjectStatus::STATUS_ALLOCATION => [ProjectStatus::STATUS_DRAFT, ProjectStatus::STATUS_PARTICIPANT_REPLY],
        ProjectStatus::STATUS_SYNDICATION_FINISHED => [ProjectStatus::STATUS_DRAFT, ProjectStatus::STATUS_PARTICIPANT_REPLY, ProjectStatus::STATUS_ALLOCATION],
        ProjectStatus::STATUS_SYNDICATION_CANCELLED => [ProjectStatus::STATUS_DRAFT, ProjectStatus::STATUS_PARTICIPANT_REPLY],
    ];

    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @param ObjectManager $manager
     *
     * @throws ReflectionException
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Clients $otherUser */
        $otherUser = $this->getReference(UserFixtures::PARTICIPANT);
        // We set the user in the tokenStorage to avoid conflict with ProjectStatusCreatedListener
        $this->login(UserFixtures::ADMIN);
        $this->manager = $manager;
        $projectDraftParticipation = $this->createProject('Project draft', ProjectStatus::STATUS_DRAFT);
        $projectDraft = $this->createProject('Project created', ProjectStatus::STATUS_DRAFT);
        $projectReply = $this->createProject('Project reply', ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $projectAllocation = $this->createProject('Project allocation', ProjectStatus::STATUS_ALLOCATION);
        $projectFinished = $this->createProject('Project finished', ProjectStatus::STATUS_SYNDICATION_FINISHED);
        $projectArchived = $this->createProject('Project archived', ProjectStatus::STATUS_SYNDICATION_CANCELLED);
        $projectDraftOtherUser = $this->createProject('Project other user', ProjectStatus::STATUS_DRAFT, $otherUser->getCurrentStaff());
        $this->addReference(self::PROJECT_ALLOCATION, $projectAllocation);
        $this->addReference(self::PROJECT_REPLY, $projectReply);
        $this->addReference(self::PROJECT_DRAFT, $projectDraft);
        $this->addReference(self::PROJECT_DRAFT_PARTICIPATION, $projectDraftParticipation);
        $this->addReference(self::PROJECT_OTHER_USER, $projectDraftOtherUser);
        $this->addReference(self::PROJECT_FINISHED, $projectFinished);
        $this->addReference(self::PROJECT_ARCHIVED, $projectArchived);
        $manager->flush();
    }

    /**
     * @param string     $title
     * @param int        $status
     * @param Staff|null $staff
     *
     * @return Project
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function createProject(string $title, int $status, ?Staff $staff = null): Project
    {
        /** @var Staff $staff */
        $staff = $staff ?: $this->getReference(StaffFixtures::ADMIN);

        // NDA File
        $ndaFile = (new File());
        $ndaFileVersion = (new FileVersion('/fake.pdf', $staff, $ndaFile, 'user_attachment', '', 'application/pdf'))->setOriginalName($title . ' NDA.pdf');
        $ndaFile->setCurrentFileVersion($ndaFileVersion);

        // Project
        $project = (new Project(
            $staff,
            'RISK-GROUP-1',
            new Money('EUR', '5000000'),
            $this->getReference(MarketSegmentFixtures::SEGMENT1)
        ))
            ->setTitle($title)
            ->setNda($ndaFile)
            ->setInternalRatingScore('B')
            ->setFundingSpecificity('FSA')
            ->setParticipationType(Project::PROJECT_PARTICIPATION_TYPE_DIRECT)
            ->setSyndicationType(Project::PROJECT_SYNDICATION_TYPE_PRIMARY)
            ->setInterestExpressionEnabled(false) // "Réponse ferme"
            ->setParticipantReplyDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+70 days', '+1 year')))
            ->setAllocationDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+1 year', '+2 year')))
            ->setDescription($this->faker->sentence);
        $this->forcePublicId($project, Urlizer::urlize($title));

        // Project Status
        $projectStatus = new ProjectStatus($project, $status, $staff);
        $project->setCurrentStatus($projectStatus);
        $this->createPreviousStatuses($project, $staff);

        // Persist
        $this->manager->persist($projectStatus);
        $this->manager->persist($ndaFile);
        $this->manager->persist($project);

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

    /**
     * @param Project $project
     * @param Staff   $addedBy
     *
     * @throws Exception
     */
    private function createPreviousStatuses(Project $project, Staff $addedBy)
    {
        foreach (static::PREVIOUS_STATUSES[$project->getCurrentStatus()->getStatus()] ?? [] as $index => $previousStatus) {
            $this->manager->persist(new ProjectStatus($project, $previousStatus, $addedBy));
        }
    }
}
