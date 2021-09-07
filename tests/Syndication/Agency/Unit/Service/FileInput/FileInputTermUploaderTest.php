<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Service\FileInput;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\File;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Syndication\Agency\Security\Voter\TermVoter;
use KLS\Syndication\Agency\Service\FileInput\FileInputTermUploader;
use KLS\Test\Core\Unit\Traits\FileInputEntitiesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\Service\FileInput\FileInputTermUploader
 *
 * @internal
 */
class FileInputTermUploaderTest extends TestCase
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
        $targetEntity = $this->createTerm($this->createStaff());

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
        yield 'project' => [$this->createArrangementProject($staff)];
        yield 'projectParticipation' => [$this->createProjectParticipation($staff, $this->createArrangementProject($staff))];
    }

    /**
     * @covers ::upload
     */
    public function testUpload(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createTerm($staff);
        $fileInput    = $this->createFileInput($targetEntity);
        $file         = new File();
        $user         = $staff->getUser();

        $this->security->isGranted(TermVoter::ATTRIBUTE_EDIT, $targetEntity)->shouldBeCalledOnce()->willReturn(true);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, Argument::type(File::class), ['termId' => $targetEntity->getId()])->shouldBeCalledOnce();

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, $file);

        static::assertInstanceOf(File::class, $result);
        static::assertNotNull($targetEntity->getBorrowerDocument());
    }

    /**
     * @covers ::upload
     */
    public function testUploadAccessDenied(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createTerm($staff);
        $fileInput    = $this->createFileInput($targetEntity);
        $file         = new File();
        $user         = $staff->getUser();

        $this->security->isGranted(TermVoter::ATTRIBUTE_EDIT, $targetEntity)->shouldBeCalledOnce()->willReturn(false);
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    /**
     * @covers ::upload
     *
     * @dataProvider exceptionProvider
     */
    public function testUploadException(string $exceptionClass): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createTerm($staff);
        $fileInput    = $this->createFileInput($targetEntity);
        $file         = new File();
        $user         = $staff->getUser();

        $this->security->isGranted(TermVoter::ATTRIBUTE_EDIT, $targetEntity)->shouldBeCalledOnce()->willReturn(true);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, Argument::type(File::class), ['termId' => $targetEntity->getId()])
            ->shouldBeCalledOnce()
            ->willThrow($exceptionClass)
        ;

        static::expectException($exceptionClass);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    public function exceptionProvider(): iterable
    {
        yield EnvironmentIsBrokenException::class => [EnvironmentIsBrokenException::class];
        yield IOException::class => [IOException::class];
        yield ORMException::class => [ORMException::class];
        yield OptimisticLockException::class => [OptimisticLockException::class];
    }

    private function createTestObject(): FileInputTermUploader
    {
        return new FileInputTermUploader(
            $this->security->reveal(),
            $this->fileUploadManager->reveal()
        );
    }
}
