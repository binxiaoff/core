<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\File;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Faker\Provider\{Base, Internet};
use League\Flysystem\{FileExistsException, FilesystemInterface};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\{Clients, Company, File, Staff};
use Unilend\Repository\FileRepository;
use Unilend\Service\File\FileUploadManager;
use Unilend\Service\FileSystem\FileSystemHelper;

/**
 * @coversDefaultClass \Unilend\Service\File\FileUploadManager
 *
 * @internal
 */
class FileUploadManagerTest extends TestCase
{
    /** @var FilesystemInterface|ObjectProphecy */
    private $userAttachmentFilesystem;

    /** @var FileSystemHelper|ObjectProphecy */
    private $fileSystemHelper;

    /** @var FileRepository|ObjectProphecy */
    private $fileRepository;

    /** @var MessageBusInterface|ObjectProphecy */
    private $messageBus;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->userAttachmentFilesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileSystemHelper         = $this->prophesize(FileSystemHelper::class);
        $this->fileRepository           = $this->prophesize(FileRepository::class);
        $this->messageBus               = $this->prophesize(MessageBusInterface::class);
    }

    /**
     * @covers ::upload
     *
     * @dataProvider uploadDataProvider
     *
     * @param File|null   $file
     * @param string|null $description
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReflectionException
     * @throws Exception
     */
    public function testUpload(
        ?File $file = null,
        ?string $description = null
    ): void {
        $idClientsReflectionProperty = new ReflectionProperty(Clients::class, 'id');
        $idClientsReflectionProperty->setAccessible(true);
        $uploader   = new Clients('test@' . Internet::safeEmailDomain());
        $uploaderId = Base::randomDigitNotNull() + 1;
        $idClientsReflectionProperty->setValue($uploader, $uploaderId);
        $uploaderStaff = new Staff(new Company('test'), $uploader, $this->prophesize(Staff::class)->reveal());

        $filePath         = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        $originalFileName = Base::asciify(str_repeat('*', 20));
        fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, $originalFileName, null, null, true);

        $fileWriter = $this->fileSystemHelper->writeTempFileToFileSystem(
            Argument::exact($uploadedFile->getPathname()),
            Argument::exact($this->userAttachmentFilesystem),
            Argument::containingString((string) $uploader->getId()),
            Argument::exact(true),
        );

        $fileNameNormalizer = $this->fileSystemHelper->normalizeFileName(Argument::type('string'));
        $fileNameNormalizer->willReturn($originalFileName);

        $encryptionKey = Base::asciify(str_repeat('*', 440));
        $fileWriter->willReturn($encryptionKey);

        $this->createTestObject()->upload(
            $uploadedFile,
            $uploaderStaff,
            $file,
            $description
        );

        $fileWriter->shouldHaveBeenCalled();
        $this->fileRepository->save(Argument::exact($file))->shouldHaveBeenCalled();
        $fileNameNormalizer->shouldHaveBeenCalled();

        $pathInfo                    = pathinfo($file->getCurrentFileVersion()->getPath());
        $uploadedFilename            = $pathInfo['filename'];
        $uploadedDirname             = $pathInfo['dirname'];
        $originalFilename            = pathinfo($originalFileName, PATHINFO_FILENAME);
        $uploadedFilePathDirectories = explode(DIRECTORY_SEPARATOR, trim($uploadedDirname, '/'));

        static::assertNotSame($originalFilename, $uploadedFilename);

        static::assertGreaterThanOrEqual(3, $uploadedFilePathDirectories, 'minimum number of directories');

        static::assertSame(1, mb_strlen(array_shift($uploadedFilePathDirectories)), 'first mandatory subdirectory');
        static::assertSame(1, mb_strlen(array_shift($uploadedFilePathDirectories)), 'second mandatory subdirectory');

        static::assertSame($uploaderStaff, $file->getCurrentFileVersion()->getAddedBy());
        static::assertStringContainsString((string) $uploader->getId(), $file->getCurrentFileVersion()->getPath());
        static::assertSame($description, $file->getDescription());
        static::assertSame('inode/x-empty', $file->getCurrentFileVersion()->getMimeType());
        static::assertSame($encryptionKey, $file->getCurrentFileVersion()->getPlainEncryptionKey());
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function uploadDataProvider(): array
    {
        $file = (new File())->setDescription(Base::randomLetter());

        return [
            'file without description' => [$file, null],
            'file with description'    => [$file, Base::randomLetter()],
        ];
    }

    /**
     * @return FileUploadManager
     */
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
