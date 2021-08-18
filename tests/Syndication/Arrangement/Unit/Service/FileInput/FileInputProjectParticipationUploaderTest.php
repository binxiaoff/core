<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Service\FileInput;

use KLS\Core\Entity\File;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use KLS\Syndication\Arrangement\Service\FileInput\FileInputProjectParticipationUploader;
use KLS\Test\Core\Unit\Traits\FileInputEntitiesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Service\FileInput\FileInputProjectParticipationUploader
 *
 * @internal
 */
class FileInputProjectParticipationUploaderTest extends TestCase
{
    use FileInputEntitiesTrait;

    /** @var Security|ObjectProphecy */
    private $security;
    /** @var FileUploadManager|ObjectProphecy */
    private $fileUploadManager;

    protected function setUp(): void
    {
        $this->security          = $this->prophesize(Security::class);
        $this->fileUploadManager = $this->prophesize(FileUploadManager::class);
    }

    protected function tearDown(): void
    {
        $this->security          = null;
        $this->fileUploadManager = null;
    }

    /**
     * @covers ::supports
     */
    public function testSupports(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createProjectParticipation($staff, $this->createArrangementProject($staff));

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

        yield 'message' => [$this->createMessage($staff)];
        yield 'term' => [$this->createTerm($staff)];
        yield 'project' => [$this->createArrangementProject($staff)];
    }

    /**
     * @covers ::upload
     */
    public function testUpload(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createProjectParticipation($staff, $this->createArrangementProject($staff));
        $token        = $this->createToken($staff, ['company' => $staff->getCompany()]);
        $fileInput    = $this->createFileInput($targetEntity);
        $user         = $staff->getUser();

        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity->getProject())->shouldBeCalledOnce()->willReturn(true);
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, Argument::type(File::class), ['projectParticipationId' => $targetEntity->getId()], $staff->getCompany())
            ->shouldBeCalledOnce()
        ;

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, null);

        static::assertInstanceOf(File::class, $result);
        static::assertNotNull($targetEntity->getNda());
    }

    /**
     * @covers ::upload
     */
    public function testUploadAccessDenied(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createProjectParticipation($staff, $this->createArrangementProject($staff));
        $fileInput    = $this->createFileInput($targetEntity);
        $user         = $staff->getUser();

        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity->getProject())->shouldBeCalledOnce()->willReturn(false);
        $this->security->getToken()->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, null);
    }

    /**
     * @covers ::upload
     */
    public function testUploadDeniedWithExistingFile(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createProjectParticipation($staff, $this->createArrangementProject($staff, ProjectStatus::STATUS_INTEREST_EXPRESSION));
        $fileInput    = $this->createFileInput($targetEntity);
        $file         = new File();
        $user         = $staff->getUser();

        $targetEntity->setPublicId();
        $targetEntity->setNda((new File())->setPublicId());

        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity->getProject())->shouldBeCalledOnce()->willReturn(true);
        $this->security->getToken()->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();

        static::expectException(RuntimeException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    private function createTestObject(): FileInputProjectParticipationUploader
    {
        return new FileInputProjectParticipationUploader(
            $this->security->reveal(),
            $this->fileUploadManager->reveal()
        );
    }
}
