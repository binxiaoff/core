<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\File;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Faker\Provider\Base;
use Faker\Provider\Internet;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\User;
use Unilend\Core\Message\File\FileUploaded;
use Unilend\Core\Repository\FileRepository;
use Unilend\Core\Service\File\FileUploadManager;
use Unilend\Core\Service\FileSystem\FileSystemHelper;

/**
 * @coversDefaultClass \Unilend\Core\Service\File\FileUploadManager
 *
 * @internal
 */
class FileUploadManagerTest extends TestCase
{
    /** @var FilesystemOperator|ObjectProphecy */
    private $userAttachmentFilesystem;

    /** @var FileSystemHelper|ObjectProphecy */
    private $fileSystemHelper;

    /** @var FileRepository|ObjectProphecy */
    private $fileRepository;

    /** @var MessageBusInterface|ObjectProphecy */
    private $messageBus;

    protected function setUp(): void
    {
        $this->userAttachmentFilesystem = $this->prophesize(FilesystemOperator::class);
        $this->fileSystemHelper         = $this->prophesize(FileSystemHelper::class);
        $this->fileRepository           = $this->prophesize(FileRepository::class);
        $this->messageBus               = $this->prophesize(MessageBusInterface::class);
    }

    /**
     * @covers ::upload
     *
     * @dataProvider uploadDataProvider
     *
     * @throws FilesystemException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws Exception
     */
    public function testUpload(?File $file, array $context): void
    {
        $idUsersReflectionProperty = new ReflectionProperty(User::class, 'id');
        $idUsersReflectionProperty->setAccessible(true);
        $uploader   = new User('test@' . Internet::safeEmailDomain());
        $uploaderId = Base::randomDigitNotNull() + 1;
        $idUsersReflectionProperty->setValue($uploader, $uploaderId);

        $filePath         = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        $originalFileName = Base::asciify(\str_repeat('*', 20));
        \fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, $originalFileName, null, null, true);

        $fileWriter = $this->fileSystemHelper->writeTempFileToFileSystem(
            Argument::exact($uploadedFile->getPathname()),
            Argument::exact($this->userAttachmentFilesystem),
            Argument::containingString((string) $uploader->getId()),
            Argument::exact(true),
        );

        $fileNameNormalizer = $this->fileSystemHelper->normalizeFileName(Argument::type('string'));
        $fileNameNormalizer->willReturn($originalFileName);

        $encryptionKey = Base::asciify(\str_repeat('*', 440));
        $fileWriter->willReturn($encryptionKey);

        $dispatcher = $this->messageBus->dispatch(Argument::exact(new FileUploaded($file, $context)));
        $dispatcher->willReturn($this->prophesize(Envelope::class)->reveal());
        $fileExistenceChecker = $this->userAttachmentFilesystem->fileExists(Argument::any());
        $fileExistenceChecker->willReturn(false);

        $this->createTestObject()->upload(
            $uploadedFile,
            $uploader,
            $file,
            $context
        );

        $fileWriter->shouldHaveBeenCalled();
        $fileNameNormalizer->shouldHaveBeenCalled();
        $this->fileRepository->save(Argument::exact($file))->shouldHaveBeenCalled();
        $dispatcher->shouldHaveBeenCalled();
        $fileExistenceChecker->shouldHaveBeenCalled();

        $pathInfo                    = \pathinfo($file->getCurrentFileVersion()->getPath());
        $uploadedFilename            = $pathInfo['filename'];
        $uploadedDirname             = $pathInfo['dirname'];
        $originalFilename            = \pathinfo($originalFileName, PATHINFO_FILENAME);
        $uploadedFilePathDirectories = \explode(DIRECTORY_SEPARATOR, \trim($uploadedDirname, '/'));

        static::assertNotSame($originalFilename, $uploadedFilename);

        static::assertGreaterThanOrEqual(3, $uploadedFilePathDirectories, 'minimum number of directories');

        static::assertSame(1, \mb_strlen(\array_shift($uploadedFilePathDirectories)), 'first mandatory subdirectory');
        static::assertSame(1, \mb_strlen(\array_shift($uploadedFilePathDirectories)), 'second mandatory subdirectory');

        static::assertSame($uploader, $file->getCurrentFileVersion()->getAddedBy());
        static::assertStringContainsString((string) $uploader->getId(), $file->getCurrentFileVersion()->getPath());
        static::assertSame('application/x-empty', $file->getCurrentFileVersion()->getMimeType());
        static::assertSame($encryptionKey, $file->getCurrentFileVersion()->getPlainEncryptionKey());
    }

    /**
     * @throws Exception
     */
    public function uploadDataProvider(): array
    {
        $file = new File();

        $fileIdReflectionProperty = new ReflectionProperty(File::class, 'id');
        $fileIdReflectionProperty->setAccessible(true);
        $fileId = Base::randomDigitNotNull() + 1;
        $fileIdReflectionProperty->setValue($file, $fileId);

        $context = ['projectId' => Base::randomDigitNotNull() + 1];

        return [
            'file ' => [$file, $context],
        ];
    }

    protected function createTestObject(): FileUploadManager
    {
        return new FileUploadManager(
            $this->fileSystemHelper->reveal(),
            $this->userAttachmentFilesystem->reveal(),
            $this->fileRepository->reveal(),
            $this->messageBus->reveal()
        );
    }
}
