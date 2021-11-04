<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\FileInput;

use KLS\Core\Entity\File;
use KLS\Core\Entity\MessageFile;
use KLS\Core\Repository\MessageFileRepository;
use KLS\Core\Repository\MessageRepository;
use KLS\Core\Security\Voter\MessageVoter;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Core\Service\FileInput\FileInputMessageUploader;
use KLS\Test\Core\Unit\Traits\FileInputTrait;
use KLS\Test\Core\Unit\Traits\MessageTrait;
use KLS\Test\Core\Unit\Traits\TokenTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\AgencyProjectTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\TermTrait;
use KLS\Test\Syndication\Arrangement\Unit\Traits\ArrangementProjectSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * @coversDefaultClass \KLS\Core\Service\FileInput\FileInputMessageUploader
 *
 * @internal
 */
class FileInputMessageUploaderTest extends TestCase
{
    use UserStaffTrait;
    use TokenTrait;
    use FileInputTrait;
    use MessageTrait;
    use TermTrait;
    use AgencyProjectTrait;
    use ArrangementProjectSetTrait;
    use ProphecyTrait;

    /** @var Security|ObjectProphecy */
    private $security;
    /** @var FileUploadManager|ObjectProphecy */
    private $fileUploadManager;
    /** @var MessageFileRepository|ObjectProphecy */
    private $messageFileRepository;
    /** @var MessageRepository|ObjectProphecy */
    private $messageRepository;

    protected function setUp(): void
    {
        $this->security              = $this->prophesize(Security::class);
        $this->fileUploadManager     = $this->prophesize(FileUploadManager::class);
        $this->messageFileRepository = $this->prophesize(MessageFileRepository::class);
        $this->messageRepository     = $this->prophesize(MessageRepository::class);
    }

    protected function tearDown(): void
    {
        $this->security              = null;
        $this->fileUploadManager     = null;
        $this->messageFileRepository = null;
        $this->messageRepository     = null;
    }

    /**
     * @covers ::supports
     */
    public function testSupports(): void
    {
        $targetEntity = $this->createMessage($this->createStaff());

        $uploader = $this->createTestObject();
        static::assertTrue($uploader->supports($targetEntity));
    }

    /**
     * @covers ::supports
     *
     * @dataProvider notSupportsProvider
     *
     * @param mixed $targetEntity
     */
    public function testNotSupports($targetEntity): void
    {
        $uploader = $this->createTestObject();
        static::assertFalse($uploader->supports($targetEntity));
    }

    public function notSupportsProvider(): iterable
    {
        $staff = $this->createStaff();

        yield 'term' => [$this->createTerm($staff)];
        yield 'project' => [$this->createArrangementProject($staff)];
        yield 'projectParticipation' => [$this->createProjectParticipation($staff, $this->createArrangementProject($staff))];
    }

    /**
     * @covers ::upload
     */
    public function testUpload(): void
    {
        $staff                = $this->createStaff();
        $targetEntity         = ($this->createMessage($staff))->setBroadcast('test');
        $token                = $this->createToken($staff->getUser(), ['company' => $staff->getCompany()]);
        $fileInput            = $this->createFileInput($targetEntity);
        $user                 = $staff->getUser();
        $file                 = new File();
        $messagesToBeAttached = [$this->createMessage($staff), $this->createMessage($staff)];

        $this->security->isGranted(MessageVoter::ATTRIBUTE_ATTACH_FILE, $targetEntity)->shouldBeCalledOnce()->willReturn(true);
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, [], $staff->getCompany())->shouldBeCalledOnce();
        $this->messageRepository->findBy(['broadcast' => $targetEntity->getBroadcast()])->shouldBeCalledOnce()->willReturn($messagesToBeAttached);
        $this->messageFileRepository->persist(Argument::type(MessageFile::class))->shouldBeCalledTimes(2);
        $this->messageFileRepository->flush()->shouldBeCalledOnce();

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, $file);

        static::assertInstanceOf(File::class, $result);
    }

    /**
     * @covers ::upload
     */
    public function testUploadAccessDenied(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = ($this->createMessage($staff))->setBroadcast('test');
        $fileInput    = $this->createFileInput($targetEntity);
        $user         = $staff->getUser();
        $file         = new File();

        $this->security->isGranted(MessageVoter::ATTRIBUTE_ATTACH_FILE, $targetEntity)->shouldBeCalledOnce()->willReturn(false);
        $this->security->getToken()->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();
        $this->messageRepository->findBy(Argument::any())->shouldNotBeCalled();
        $this->messageFileRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->messageFileRepository->flush()->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    /**
     * @covers ::upload
     */
    public function testUploadWithMessageNotBroadcasted(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createMessage($staff);
        $token        = $this->createToken($staff->getUser(), ['company' => $staff->getCompany()]);
        $fileInput    = $this->createFileInput($targetEntity);
        $user         = $staff->getUser();

        $this->security->isGranted(MessageVoter::ATTRIBUTE_ATTACH_FILE, $targetEntity)->shouldBeCalledOnce()->willReturn(true);
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, Argument::type(File::class), [], $staff->getCompany())->shouldBeCalledOnce();
        $this->messageRepository->findBy(Argument::any())->shouldNotBeCalled();
        $this->messageFileRepository->persist(Argument::type(MessageFile::class))->shouldBeCalledOnce();
        $this->messageFileRepository->flush()->shouldBeCalledOnce();

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, null);

        static::assertInstanceOf(File::class, $result);
    }

    private function createTestObject(): FileInputMessageUploader
    {
        return new FileInputMessageUploader(
            $this->security->reveal(),
            $this->fileUploadManager->reveal(),
            $this->messageFileRepository->reveal(),
            $this->messageRepository->reveal()
        );
    }
}
