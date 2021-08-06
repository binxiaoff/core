<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionException;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\DataFixtures\UserFixtures;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\FundingSpecificity;
use Unilend\Core\Entity\Constant\SyndicationModality\ParticipationType;
use Unilend\Core\Entity\Constant\SyndicationModality\SyndicationType;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullablePerson;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectStatus;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const PROJECT_DRAFT                    = 'PROJECT_DRAFT';
    public const PROJECT_DRAFT_PARTICIPATION      = 'PROJECT_DRAFT_PARTICIPATION';
    public const PROJECT_INTEREST                 = 'PROJECT_INTEREST';
    public const PROJECT_REPLY                    = 'PROJECT_REPLY';
    public const PROJECT_REPLY_COMMITTEE_ACCEPTED = 'PROJECT_REPLY_COMMITTEE_ACCEPTED';
    public const PROJECT_REPLY_COMMITTEE_REFUSED  = 'PROJECT_REPLY_COMMITTEE_REFUSED';
    public const PROJECT_REPLY_COMMITTEE_PENDING  = 'PROJECT_REPLY_COMMITTEE_PENDING';
    public const PROJECT_ALLOCATION               = 'PROJECT_ALLOCATION';
    public const PROJECT_FINISHED                 = 'PROJECT_FINISHED';
    public const PROJECT_ARCHIVED                 = 'PROJECT_ARCHIVED';
    public const PROJECT_OTHER_USER               = 'PROJECT_OTHER_USER';

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
        ProjectStatus::STATUS_PARTICIPANT_REPLY     => [ProjectStatus::STATUS_DRAFT],
        ProjectStatus::STATUS_ALLOCATION            => [ProjectStatus::STATUS_DRAFT, ProjectStatus::STATUS_PARTICIPANT_REPLY],
        ProjectStatus::STATUS_SYNDICATION_FINISHED  => [ProjectStatus::STATUS_DRAFT, ProjectStatus::STATUS_PARTICIPANT_REPLY, ProjectStatus::STATUS_ALLOCATION],
        ProjectStatus::STATUS_SYNDICATION_CANCELLED => [ProjectStatus::STATUS_DRAFT, ProjectStatus::STATUS_PARTICIPANT_REPLY],
    ];

    private ObjectManager $manager;

    /**
     * @return \string[][]
     */
    public static function getProjectNamesByStatus()
    {
        return [
            ProjectStatus::STATUS_DRAFT => [
                'Project created'    => self::PROJECT_DRAFT,
                'Project draft'      => self::PROJECT_DRAFT_PARTICIPATION,
                'Project other user' => self::PROJECT_OTHER_USER,
            ],
            ProjectStatus::STATUS_INTEREST_EXPRESSION => [
                'Project interest' => self::PROJECT_INTEREST,
            ],
            ProjectStatus::STATUS_PARTICIPANT_REPLY => [
                'Project reply'       => self::PROJECT_REPLY,
                'Project reply c acc' => self::PROJECT_REPLY_COMMITTEE_ACCEPTED,
                'Project reply c ref' => self::PROJECT_REPLY_COMMITTEE_REFUSED,
                'Project reply c pen' => self::PROJECT_REPLY_COMMITTEE_PENDING,
            ],
            ProjectStatus::STATUS_ALLOCATION => [
                'Project allocation' => self::PROJECT_ALLOCATION,
            ],
            ProjectStatus::STATUS_SYNDICATION_FINISHED => [
                'Project finished' => self::PROJECT_FINISHED,
            ],
            ProjectStatus::STATUS_SYNDICATION_CANCELLED => [
                'Project archived' => self::PROJECT_ARCHIVED,
            ],
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function load(ObjectManager $manager): void
    {
        // We set the user in the tokenStorage to avoid conflict with ProjectStatusCreatedListener. This should be executed first because it set user.CurrentStaff
        $this->login(StaffFixtures::ADMIN);

        $this->manager = $manager;

        foreach (self::getProjectNamesByStatus() as $status => $projectNames) {
            foreach ($projectNames as $projectName => $fixtureReferenceStatus) {
                /** @var Staff $staff */
                $staff = (self::PROJECT_OTHER_USER === $fixtureReferenceStatus) ?
                    $this->getReference(UserFixtures::PARTICIPANT)->getCurrentStaff() :
                    $this->getReference(StaffFixtures::ADMIN);

                $project = $this->createProject($projectName, $status, $staff);

                $this->addReference($fixtureReferenceStatus, $project);
            }
        }
        $manager->flush();
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function createProject(string $title, int $status, ?Staff $staff = null): Project
    {
        /** @var Staff $staff */
        $staff = $staff ?: $this->getReference(StaffFixtures::ADMIN);

        // NDA File
        $ndaFile        = (new File());
        $ndaFileVersion = (new FileVersion('/fake.pdf', $staff->getUser(), $ndaFile, 'user_attachment', '', 'application/pdf', $staff->getCompany()))
            ->setOriginalName($title . ' NDA.pdf')
        ;
        $ndaFile->setCurrentFileVersion($ndaFileVersion);

        $companyGroupTags = $staff->getCompany()->getCompanyGroupTags();

        // Project
        $project = (new Project(
            $staff,
            'RISK-GROUP-1',
            new Money('EUR', '5000000')
        ))
            ->setTitle($title)
            ->setNda($ndaFile)
            ->setInternalRatingScore(CAInternalRating::B)
            ->setFundingSpecificity(FundingSpecificity::FSA)
            ->setParticipationType(ParticipationType::DIRECT)
            ->setSyndicationType(SyndicationType::PRIMARY)
            ->setInterestExpressionEnabled(ProjectStatus::STATUS_INTEREST_EXPRESSION === $status)
            ->setInterestExpressionDeadline(
                ProjectStatus::STATUS_INTEREST_EXPRESSION === $status
                    ? DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+10 days', '+1 year'))
                    : null
            )
            ->setCompanyGroupTag(\reset($companyGroupTags) ?: null)
            ->setParticipantReplyDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+70 days', '+1 year')))
            ->setAllocationDeadline(DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+1 year', '+2 year')))
            ->setPrivilegedContactPerson(
                (new NullablePerson())
                    ->setEmail($staff->getUser()->getEmail())
                    ->setFirstName($staff->getUser()->getFirstName())
                    ->setLastName($staff->getUser()->getLastName())
                    ->setPhone($staff->getUser()->getPhone())
                    ->setOccupation($staff->getUser()->getJobFunction())
                    ->setParentUnit('Unit')
            )
            ->setDescription($this->faker->sentence)
        ;
        $this->forcePublicId($project, Urlizer::urlize($title));

        // Persist
        $this->manager->persist($ndaFile);
        $this->manager->persist($project);

        // This step create each project status the project had been. For each status, the listener StatusCreatedListener set project.currentStatus with.
        $this->createPreviousStatuses($project, $staff);

        // Then we add the project last Status we want and the listener  Unilend\Core\Listener\Doctrine\Lifecycle\StatusCreatedListener set the project.currentStatus with that one.
        $projectStatus = new ProjectStatus($project, $status, $staff);
        $this->manager->persist($projectStatus);

        return $project;
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

    /**
     * Needed because we do not record the current status in the statuses array and this array is not persisted.
     *
     * @throws Exception
     */
    private function createPreviousStatuses(Project $project, Staff $addedBy)
    {
        foreach (static::PREVIOUS_STATUSES[$project->getCurrentStatus()->getStatus()] ?? [] as $index => $previousStatus) {
            $this->manager->persist(new ProjectStatus($project, $previousStatus, $addedBy));
        }
        $this->manager->flush();
    }
}
