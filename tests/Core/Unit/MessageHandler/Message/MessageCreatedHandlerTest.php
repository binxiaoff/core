<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\Message;

use InvalidArgumentException;
use KLS\Core\Entity\Message;
use KLS\Core\Message\Message\MessageCreated;
use KLS\Core\MessageHandler\Message\MessageCreatedHandler;
use KLS\Core\Repository\MessageRepository;
use KLS\Core\Repository\MessageStatusRepository;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Test\Core\Unit\Traits\MessageTrait;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Arrangement\Unit\Traits\ArrangementProjectSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\Message\MessageCreatedHandler
 *
 * @internal
 */
class MessageCreatedHandlerTest extends TestCase
{
    use ArrangementProjectSetTrait;
    use MessageTrait;
    use ProphecyTrait;
    use UserStaffTrait;
    use PropertyValueTrait;

    /** @var MessageRepository|ObjectProphecy */
    private $messageRepository;

    /** @var MessageStatusRepository|ObjectProphecy */
    private $messageStatusRepository;

    protected function setUp(): void
    {
        $this->messageRepository       = $this->prophesize(MessageRepository::class);
        $this->messageStatusRepository = $this->prophesize(MessageStatusRepository::class);
    }

    protected function tearDown(): void
    {
        $this->messageRepository       = null;
        $this->messageStatusRepository = null;
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider messageDataProvider
     */
    public function testInvoke(
        MessageCreated $messageCreated,
        ?Message $message,
        ?string $exception
    ): void {
        $this->messageRepository->find($messageCreated->getMessageId())->shouldBeCalledOnce()->willReturn($message);

        if (null === $message || null === $message->getMessageThread()->getProjectParticipation()) {
            $this->expectException($exception);
        } else {
            $projectParticipation = $message->getMessageThread()->getProjectParticipation();
            $project              = $projectParticipation->getProject();

            $projectParticipationMembers = $message->getMessageThread()->getProjectParticipation()
                ->getProjectParticipationMembers()
            ;

            if ($message->getSender()->getCompany() === $project->getArranger()) {
                foreach ($projectParticipationMembers as $projectParticipationMember) {
                    if (
                        false === $projectParticipationMember->isArchived()
                        && $projectParticipationMember->getStaff()->isActive()
                    ) {
                        $this->messageStatusRepository->persist(Argument::that(
                            static fn ($messageStatus) => $messageStatus->getMessage()
                                === $message && $messageStatus->getRecipient()
                                === $projectParticipationMember->getStaff()
                        ))->shouldBeCalledOnce();
                    }
                }
            }

            $participationArrangerMembers = $projectParticipation->getProject()->getArrangerProjectParticipation()
                ->getActiveProjectParticipationMembers()
            ;
            if ($message->getSender()->getCompany() !== $project->getArranger()) {
                foreach ($participationArrangerMembers as $participationArrangerMember) {
                    if (
                        false === $participationArrangerMember->isArchived()
                        && $participationArrangerMember->getStaff()->isActive()
                    ) {
                        $this->messageStatusRepository->persist(Argument::that(
                            static fn ($messageStatus) => $messageStatus->getMessage()
                                === $message && $messageStatus->getRecipient()
                                === $participationArrangerMember->getStaff()
                        ))->shouldBeCalledOnce();
                    }
                }
            }

            $this->messageStatusRepository->flush()->shouldBeCalledOnce();
        }

        $this->createTestObject()($messageCreated);
    }

    /**
     * @throws \Exception
     */
    public function messageDataProvider(): iterable
    {
        $staff                       = $this->createStaff();
        $staff2                      = $this->createStaff();
        $messageSentByArranger       = $this->createMessage($staff);
        $messageWithoutParticipation = $this->createMessage($this->createStaff());

        $messageCreated = new MessageCreated($messageSentByArranger);

        $arrangementProject   = $this->createArrangementProject($staff);
        $projectParticipation = $this->createProjectParticipation($staff2, $arrangementProject);
        $member               = new ProjectParticipationMember($projectParticipation, $staff2, $staff);

        $messageSentByAParticipant = $this->createMessage($this->createStaff());

        $projectParticipation->addProjectParticipationMember($member);
        $messageSentByArranger->getMessageThread()->setProjectParticipation($projectParticipation);
        $messageSentByAParticipant->getMessageThread()->setProjectParticipation($projectParticipation);

        yield 'message-not-found' => [$messageCreated, null, InvalidArgumentException::class];
        yield 'project-participation-not-found' => [
            $messageCreated,
            $messageWithoutParticipation,
            InvalidArgumentException::class,
        ];
        yield 'message-sent-by-arranger-to-participant' => [
            $messageCreated,
            $messageSentByArranger,
            null,
        ];
        yield 'message-sent-by-participant-to-arranger' => [
            $messageCreated,
            $messageSentByAParticipant,
            null,
        ];
    }

    private function createTestObject(): MessageCreatedHandler
    {
        return new MessageCreatedHandler(
            $this->messageRepository->reveal(),
            $this->messageStatusRepository->reveal()
        );
    }
}
