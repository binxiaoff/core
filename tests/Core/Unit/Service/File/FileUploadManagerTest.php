<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\File;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Faker\Provider\Base;
use Faker\Provider\Internet;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use KLS\Core\Message\File\FileUploaded;
use KLS\Core\Repository\FileRepository;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Core\Service\FileSystem\FileSystemHelper;
use KLS\Test\Core\Unit\Traits\CompanyTrait;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @coversDefaultClass \KLS\Core\Service\File\FileUploadManager
 *
 * @internal
 */
class FileUploadManagerTest extends TestCase
{
    use ProphecyTrait;
    use PropertyValueTrait;
    use CompanyTrait;

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
        $uploaderId = Base::randomDigitNotNull() + 1;
        $uploader   = new User('test@' . Internet::safeEmailDomain());
        $this->forcePropertyValue($uploader, 'id', $uploaderId);
        $company = $this->createCompany();

        $filePath         = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        $originalFileName = Base::asciify(\str_repeat('*', 20));
        \fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, $originalFileName, null, null, true);

        $uploadDirectory = $this->fileSystemHelper->normalizeDirectory(DIRECTORY_SEPARATOR, (string) $uploaderId)
            ->shouldBeCalledOnce()->willReturn($uploaderId)
        ;

        $fileWriter = $this->fileSystemHelper->writeTempFileToFileSystem(
            Argument::exact($uploadedFile->getPathname()),
            $this->userAttachmentFilesystem,
            Argument::containingString((string) $uploader->getId()),
            true,
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
            $context,
            $company
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
        static::assertSame(
            \mb_strlen((string) $uploaderId),
            \mb_strlen(\array_shift($uploadedFilePathDirectories)),
            'first mandatory subdirectory'
        );
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
        $this->forcePropertyValue($file, 'id', Base::randomDigitNotNull() + 1);

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
