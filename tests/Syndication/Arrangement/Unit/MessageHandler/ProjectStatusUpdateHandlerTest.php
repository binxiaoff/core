<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\MessageHandler;

use Exception;
use Http\Client\Exception as HttpClientException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Message\Project\ProjectStatusUpdated;
use KLS\Syndication\Arrangement\MessageHandler\Project\ProjectStatusUpdatedHandler;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Service\Project\SlackNotifier\ProjectUpdateNotifier;
use KLS\Syndication\Arrangement\Service\ProjectParticipationMember\ProjectParticipationMemberNotifier;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Syndication\Arrangement\Unit\Traits\ArrangementProjectSetTrait;
use Nexy\Slack\Exception\SlackApiException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @covers \KLS\Syndication\Arrangement\MessageHandler\Project\ProjectStatusUpdatedHandler
 *
 * @internal
 */
class ProjectStatusUpdateHandlerTest extends TestCase
{
    use ProphecyTrait;
    use ArrangementProjectSetTrait;
    use PropertyValueTrait;

    private ?ObjectProphecy $projectRepository;

    private ?ObjectProphecy $participationMemberNotifier;

    private ?ObjectProphecy $projectUpdateNotifier;

    protected function setUp(): void
    {
        $this->projectRepository           = $this->prophesize(ProjectRepository::class);
        $this->participationMemberNotifier = $this->prophesize(ProjectParticipationMemberNotifier::class);
        $this->projectUpdateNotifier       = $this->prophesize(ProjectUpdateNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->projectRepository           = null;
        $this->participationMemberNotifier = null;
        $this->projectUpdateNotifier       = null;
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider providerInvoke
     *
     * @throws Exception
     * @throws SlackApiException
     * @throws HttpClientException
     */
    public function testInvokeWithProjectNotFound(int $old, int $new)
    {
        $project = $this->createProject($new);
        $project->setCurrentStatus(new ProjectStatus($project, $new, $this->prophesize(Staff::class)->reveal()));
        $this->projectRepository->find($project->getId())->willReturn(null);

        $oldStatus = new ProjectStatus(
            $project,
            $old,
            new Staff(new User(\mt_rand() . '@new.com'), $project->getArranger()->getRootTeam())
        );

        $projectStatusUpdatedHandler = new ProjectStatusUpdatedHandler(
            $this->projectRepository->reveal(),
            $this->participationMemberNotifier->reveal(),
            $this->projectUpdateNotifier->reveal()
        );

        $projectStatusUpdatedHandler(new ProjectStatusUpdated(
            $project,
            $oldStatus,
            $project->getCurrentStatus()
        ));

        $this->projectRepository->find($project->getId())->shouldHaveBeenCalledOnce();
        $this->participationMemberNotifier->notifyMemberAdded(Argument::any())->shouldNotHaveBeenCalled();
        $this->projectUpdateNotifier->notify(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @throws Exception
     */
    public function providerInvoke(): iterable
    {
        $transitions = [
            ProjectStatus::STATUS_DRAFT => [
                ProjectStatus::STATUS_INTEREST_EXPRESSION,
                ProjectStatus::STATUS_PARTICIPANT_REPLY,
            ],
            ProjectStatus::STATUS_INTEREST_EXPRESSION => [
                ProjectStatus::STATUS_PARTICIPANT_REPLY,
                ProjectStatus::STATUS_SYNDICATION_CANCELLED,
            ],
            ProjectStatus::STATUS_PARTICIPANT_REPLY => [
                ProjectStatus::STATUS_ALLOCATION,
                ProjectStatus::STATUS_SYNDICATION_CANCELLED,
            ],
            ProjectStatus::STATUS_ALLOCATION => [
                ProjectStatus::STATUS_CONTRACTUALISATION,
                ProjectStatus::STATUS_SYNDICATION_CANCELLED,
            ],
            ProjectStatus::STATUS_CONTRACTUALISATION => [
                ProjectStatus::STATUS_SYNDICATION_FINISHED,
                ProjectStatus::STATUS_SYNDICATION_CANCELLED,
            ],
        ];

        foreach ($transitions as $old => $news) {
            foreach ($news as $new) {
                yield 'It should send the correct notifications with a project going from ' . $old . ' to ' . $new => [
                    $old,
                    $new,
                ];
            }
        }
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider providerInvoke
     *
     * @throws HttpClientException
     * @throws SlackApiException
     * @throws Exception
     */
    public function testInvoke(int $old, int $new)
    {
        $project = $this->createProject($new);
        $project->setCurrentStatus(new ProjectStatus($project, $new, $this->prophesize(Staff::class)->reveal()));
        $this->projectRepository->find($project->getId())->willReturn($project);

        $oldStatus = new ProjectStatus(
            $project,
            $old,
            new Staff(new User(\mt_rand() . '@new2.com'), $project->getArranger()->getRootTeam())
        );

        $projectStatusUpdatedHandler = new ProjectStatusUpdatedHandler(
            $this->projectRepository->reveal(),
            $this->participationMemberNotifier->reveal(),
            $this->projectUpdateNotifier->reveal()
        );

        $projectStatusUpdatedHandler(
            new ProjectStatusUpdated(
                $project,
                $oldStatus,
                $project->getCurrentStatus()
            )
        );

        $this->projectRepository->find($project->getId())->shouldHaveBeenCalledOnce();
        if (
            \in_array(
                $project->getCurrentStatus()->getStatus(),
                [ProjectStatus::STATUS_INTEREST_EXPRESSION, ProjectStatus::STATUS_PARTICIPANT_REPLY],
                true
            )
        ) {
            // There is 4 call made:
            // - One for each of the participationMember in
            //  - the ProjectParticipationStatus::STATUS_CREATED,
            //  - ProjectParticipationStatus::STATUS_COMMITTEE_PENDED,
            //  - ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED statuses
            // - One for the member of the arrangement participation (the staff used to created the project)
            $this->participationMemberNotifier->notifyMemberAdded(
                Argument::type(ProjectParticipationMember::class)
            )->shouldHaveBeenCalledTimes(4);
        } else {
            $this->participationMemberNotifier->notifyMemberAdded(Argument::any())->shouldNotBeCalled();
        }
        $this->projectUpdateNotifier->notify($project)->shouldHaveBeenCalledOnce();
    }

    /**
     * @throws Exception
     */
    private function createProject(int $status): Project
    {
        $user    = new User('a@a.com');
        $company = new Company('a', 'sirne');
        $staff   = new Staff($user, $company->getRootTeam());
        $project = $this->createArrangementProject($staff, $status);

        $this->forcePropertyValue($project, 'id', 1);

        $participations = \array_map(
            static function ($status) use ($project, $staff) {
                $participation = new ProjectParticipation(
                    new Company((string) \mt_rand(), (string) \mt_rand()),
                    $project,
                    $staff
                );

                $participation->setCurrentStatus(new ProjectParticipationStatus($participation, $status, $staff));

                return $participation;
            },
            [
                ProjectParticipationStatus::STATUS_CREATED,
                ProjectParticipationStatus::STATUS_COMMITTEE_PENDED,
                ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED,
                ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER,
                ProjectParticipationStatus::STATUS_ARCHIVED_BY_PARTICIPANT,
                ProjectParticipationStatus::STATUS_COMMITTEE_REJECTED,
            ]
        );

        foreach ($participations as $participation) {
            $active = new ProjectParticipationMember(
                $participation,
                new Staff(new User(\mt_rand() . '@test.com'), $participation->getParticipant()->getRootTeam()),
                $staff
            );
            $participation->addProjectParticipationMember($active);

            $archived = new ProjectParticipationMember(
                $participation,
                new Staff(new User(\mt_rand() . '@test.com'), $participation->getParticipant()->getRootTeam()),
                $staff
            );
            $archived->setArchived(new \DateTime());
            $archived->setArchivedBy($staff);

            $participation->addProjectParticipationMember($archived);

            $project->addProjectParticipation($participation);
        }

        return $project;
    }
}
