<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\File;

use KLS\Core\Entity\File;
use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageFile;
use KLS\Core\Entity\User;
use KLS\Core\Repository\MessageFileRepository;
use KLS\Core\Security\Voter\MessageVoter;
use KLS\Core\Service\File\FileDownloadMessagePermissionChecker;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Test\Core\Unit\Traits\MessageTrait;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \KLS\Core\Service\File\FileDownloadMessagePermissionChecker
 *
 * @internal
 */
class FileDownloadMessagePermissionCheckerTest extends TestCase
{
    use PropertyValueTrait;
    use UserStaffTrait;
    use MessageTrait;

    /** @var AuthorizationCheckerInterface|ObjectProphecy */
    private $authorizationChecker;

    /** @var MessageFileRepository|ObjectProphecy */
    private $messageFileRepository;

    protected function setUp(): void
    {
        $this->authorizationChecker  = $this->prophesize(AuthorizationCheckerInterface::class);
        $this->messageFileRepository = $this->prophesize(MessageFileRepository::class);
    }

    protected function tearDown(): void
    {
        $this->authorizationChecker  = null;
        $this->messageFileRepository = null;
    }

    /**
     * @covers ::check
     */
    public function testCheck(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Message::FILE_TYPE_MESSAGE_ATTACHMENT);
        $messageFile1 = new MessageFile($file, $this->createMessage($staff));
        $messageFile2 = new MessageFile($file, $this->createMessage($staff));
        $messageFiles = [$messageFile1, $messageFile2];

        $this->forcePropertyValue($file, 'id', 1);

        $this->messageFileRepository->findBy(['file' => $file])->shouldBeCalledOnce()->willReturn($messageFiles);
        $this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageFile1->getMessage())->shouldBeCalledOnce()->willReturn(false);
        $this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageFile2->getMessage())->shouldBeCalledOnce()->willReturn(true);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertTrue($result);
    }

    /**
     * @covers ::check
     *
     * @dataProvider notSupportsProvider
     */
    public function testCheckNotSupports(string $type): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, $type);

        $this->forcePropertyValue($file, 'id', 1);

        $this->messageFileRepository->findBy(Argument::any())->shouldNotBeCalled();
        $this->authorizationChecker->isGranted(Argument::cetera())->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    public function notSupportsProvider(): iterable
    {
        yield Term::FILE_TYPE_BORROWER_DOCUMENT => [Term::FILE_TYPE_BORROWER_DOCUMENT];
        yield ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA => [ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA];

        foreach (ProjectFile::getProjectFileTypes() as $type) {
            yield $type => [$type];
        }
        foreach (Project::getProjectFileTypes() as $type) {
            yield $type => [$type];
        }
    }

    /**
     * @covers ::check
     */
    public function testCheckWithoutCurrentStaff(): void
    {
        $user         = new User('user@mail.com');
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Message::FILE_TYPE_MESSAGE_ATTACHMENT);

        $this->forcePropertyValue($file, 'id', 1);

        $this->messageFileRepository->findBy(Argument::any())->shouldNotBeCalled();
        $this->authorizationChecker->isGranted(Argument::cetera())->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    /**
     * @covers ::check
     */
    public function testCheckWithoutMessageFiles(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Message::FILE_TYPE_MESSAGE_ATTACHMENT);

        $this->forcePropertyValue($file, 'id', 1);

        $this->messageFileRepository->findBy(['file' => $file])->shouldBeCalledOnce()->willReturn([]);
        $this->authorizationChecker->isGranted(Argument::cetera())->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    /**
     * @covers ::check
     */
    public function testCheckWithMessageFilesNotGranted(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Message::FILE_TYPE_MESSAGE_ATTACHMENT);
        $messageFile1 = new MessageFile($file, $this->createMessage($staff));
        $messageFiles = [$messageFile1];

        $this->forcePropertyValue($file, 'id', 1);

        $this->messageFileRepository->findBy(['file' => $file])->shouldBeCalledOnce()->willReturn($messageFiles);
        $this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageFile1->getMessage())->shouldBeCalledOnce()->willReturn(false);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    private function createTestObject(): FileDownloadMessagePermissionChecker
    {
        return new FileDownloadMessagePermissionChecker(
            $this->authorizationChecker->reveal(),
            $this->messageFileRepository->reveal()
        );
    }
}
