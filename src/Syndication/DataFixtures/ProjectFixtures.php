<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionException;
use Unilend\Core\DataFixtures\{AbstractFixtures, MarketSegmentFixtures, StaffFixtures, UserFixtures};
use Unilend\Core\Entity\{Clients, Embeddable\Money, Embeddable\NullablePerson, File, FileVersion, Staff};
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectStatus;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    public const PROJECT_DRAFT = 'PROJECT_DRAFT';
    public const PROJECT_DRAFT_PARTICIPATION = 'PROJECT_DRAFT_PARTICIPATION';
    public const PROJECT_INTEREST = 'PROJECT_INTEREST';
    public const PROJECT_REPLY = 'PROJECT_REPLY';
    public const PROJECT_REPLY_COMMITTEE_ACCEPTED = 'PROJECT_REPLY_COMMITTEE_ACCEPTED';
    public const PROJECT_REPLY_COMMITTEE_REFUSED = 'PROJECT_REPLY_COMMITTEE_REFUSED';
    public const PROJECT_REPLY_COMMITTEE_PENDING = 'PROJECT_REPLY_COMMITTEE_PENDING';
    public const PROJECT_ALLOCATION = 'PROJECT_ALLOCATION';
    public const PROJECT_FINISHED = 'PROJECT_FINISHED';
    public const PROJECT_ARCHIVED = 'PROJECT_ARCHIVED';
    public const PROJECT_OTHER_USER = 'PROJECT_OTHER_USER';

    public const PROJECTS = [
        self::PROJECT_DRAFT,
        self::PROJECT_DRAFT_PARTICIPATION,
        self::PROJECT_REPLY,
        self::PROJECT_INTEREST,
        self::PROJECT_REPLY_COMMITTEE_ACCEPTED,
        self::PROJECT_REPLY_COMMITTEE_REFUSED,
        self::PROJECT_REPLY_COMMITTEE_PENDING,
        self::PROJECT_ALLOCATION,
        self::PROJECT_FINISHED,
        self::PROJECT_ARCHIVED,
    ];

    public const PROJECTS_WITH_PARTICIPATION = [
        self::PROJECT_DRAFT_PARTICIPATION,
        self::PROJECT_INTEREST,
        self::PROJECT_REPLY,
        self::PROJECT_REPLY_COMMITTEE_ACCEPTED,
        self::PROJECT_REPLY_COMMITTEE_REFUSED,
        self::PROJECT_REPLY_COMMITTEE_PENDING,
        self::PROJECT_ALLOCATION,
        self::PROJECT_FINISHED,
        self::PROJECT_ARCHIVED,
    ];

    public const PROJECTS_WITH_PARTICIPATION_TRANCHES = [
        self::PROJECT_REPLY,
        self::PROJECT_REPLY_COMMITTEE_ACCEPTED,
        self::PROJECT_REPLY_COMMITTEE_REFUSED,
        self::PROJECT_REPLY_COMMITTEE_PENDING,
        self::PROJECT_ALLOCATION,
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
        $this->login(StaffFixtures::ADMIN);
        $this->manager = $manager;
        $projectDraft = $this->createProject('Project created', ProjectStatus::STATUS_DRAFT);
        $projectDraftParticipation = $this->createProject('Project draft', ProjectStatus::STATUS_DRAFT);
        $projectInterest = $this->createProject('Project interest', ProjectStatus::STATUS_INTEREST_EXPRESSION);
        $projectReply = $this->createProject('Project reply', ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $projectReplyCommitteeAccepted = $this->createProject('Project reply c acc', ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $projectReplyCommitteeRefused = $this->createProject('Project reply c ref', ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $projectReplyCommitteePending = $this->createProject('Project reply c pen', ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $projectAllocation = $this->createProject('Project allocation', ProjectStatus::STATUS_ALLOCATION);
        $projectFinished = $this->createProject('Project finished', ProjectStatus::STATUS_SYNDICATION_FINISHED);
        $projectArchived = $this->createProject('Project archived', ProjectStatus::STATUS_SYNDICATION_CANCELLED);
        $projectDraftOtherUser = $this->createProject('Project other user', ProjectStatus::STATUS_DRAFT, $otherUser->getCurrentStaff());
        $this->addReference(self::PROJECT_DRAFT, $projectDraft);
        $this->addReference(self::PROJECT_DRAFT_PARTICIPATION, $projectDraftParticipation);
        $this->addReference(self::PROJECT_INTEREST, $projectInterest);
        $this->addReference(self::PROJECT_REPLY, $projectReply);
        $this->addReference(self::PROJECT_REPLY_COMMITTEE_ACCEPTED, $projectReplyCommitteeAccepted);
        $this->addReference(self::PROJECT_REPLY_COMMITTEE_REFUSED, $projectReplyCommitteeRefused);
        $this->addReference(self::PROJECT_REPLY_COMMITTEE_PENDING, $projectReplyCommitteePending);
        $this->addReference(self::PROJECT_ALLOCATION, $projectAllocation);
        $this->addReference(self::PROJECT_FINISHED, $projectFinished);
        $this->addReference(self::PROJECT_ARCHIVED, $projectArchived);
        $this->addReference(self::PROJECT_OTHER_USER, $projectDraftOtherUser);
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
            ->setInternalRatingScore(Project::INTERNAL_RATING_SCORE_B)
            ->setFundingSpecificity(Project::FUNDING_SPECIFICITY_FSA)
            ->setParticipationType(Project::PROJECT_PARTICIPATION_TYPE_DIRECT)
            ->setSyndicationType(Project::PROJECT_SYNDICATION_TYPE_PRIMARY)
            ->setInterestExpressionEnabled(ProjectStatus::STATUS_INTEREST_EXPRESSION === $status)
            ->setInterestExpressionDeadline(
                ProjectStatus::STATUS_INTEREST_EXPRESSION === $status
                    ? DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+10 days', '+1 year'))
                    : null
            )
            ->setParticipantReplyDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+70 days', '+1 year')))
            ->setAllocationDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+1 year', '+2 year')))
            ->setPrivilegedContactPerson(
                (new NullablePerson())
                    ->setEmail($staff->getClient()->getEmail())
                    ->setFirstName($staff->getClient()->getFirstName())
                    ->setLastName($staff->getClient()->getLastName())
                    ->setPhone($staff->getClient()->getPhone())
                    ->setOccupation($staff->getClient()->getJobFunction())
                    ->setParentUnit('Unit')
            )
            ->setDescription($this->faker->sentence);
        $this->forcePublicId($project, Urlizer::urlize($title));

        // Project Status
        $this->createPreviousStatuses($project, $staff);
        $projectStatus = new ProjectStatus($project, $status, $staff);
        $project->setCurrentStatus($projectStatus);

        // Persist
        // Need to repersist the correct status because of listener  Unilend\Core\Listener\Doctrine\Lifecycle\StatusCreatedListener
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
     * Needed because we do not record the current status in the statuses array and this array is not persisted
     *
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
