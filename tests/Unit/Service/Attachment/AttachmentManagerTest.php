<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Attachment;

use DateTimeInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Faker\Provider\Base;
use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\{ObjectProphecy, ProphecySubjectInterface};
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, AttachmentType, Clients, Companies};
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\Attachment\AttachmentManager;
use Unilend\Service\FileSystem\FileUploadManager;

/**
 * @coversDefaultClass \Unilend\Service\Attachment\AttachmentManager
 *
 * @internal
 */
class AttachmentManagerTest extends TestCase
{
    /** @var FilesystemInterface|ObjectProphecy */
    private $userAttachmentFilesystem;

    /** @var FileUploadManager|ObjectProphecy */
    private $fileUploadManager;

    /** @var AttachmentRepository|ObjectProphecy */
    private $attachmentRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->userAttachmentFilesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileUploadManager        = $this->prophesize(FileUploadManager::class);
        $this->attachmentRepository     = $this->prophesize(AttachmentRepository::class);
    }

    /**
     * @covers ::upload
     *
     * @dataProvider uploadDataProvider
     *
     * @param AttachmentType|null $type
     * @param Companies|null      $companyOwner
     * @param string|null         $description
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReflectionException
     */
    public function testUpload(
        ?AttachmentType $type = null,
        ?Companies $companyOwner = null,
        ?string $description = null
    ): void {
        $this->fileUploadManager->uploadFile(Argument::type(UploadedFile::class), Argument::cetera())->will(
            function ($args) {
                return $args[3];
            }
        );
        $this->attachmentRepository->save(Argument::type(Attachment::class));
        $attachmentManager = $this->createTestObject();

        $idClientsReflectionProperty = new ReflectionProperty(Clients::class, 'idClient');
        $idClientsReflectionProperty->setAccessible(true);
        $owner   = new Clients();
        $ownerId = Base::randomDigitNotNull();
        $idClientsReflectionProperty->setValue($owner, $ownerId);

        $company = new Companies('CALS', '850890666');

        $uploader   = new Clients();
        $uploaderId = Base::randomDigitNotNull() + 1;
        $idClientsReflectionProperty->setValue($uploader, $uploaderId);

        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, Base::asciify(str_repeat('*', 20)), null, null, true);

        $createdAttachment = $attachmentManager->upload(
            $uploadedFile,
            $uploader,
            $type,
            $companyOwner,
            $description
        );

        static::assertSame($uploader, $createdAttachment->getAddedBy());
        static::assertStringContainsString((string) $uploader->getIdClient(), $createdAttachment->getPath());
        static::assertSame($type, $createdAttachment->getType());
        static::assertSame($companyOwner, $createdAttachment->getCompanyOwner());
        static::assertSame($description, $createdAttachment->getDescription());
    }

    /**
     * @return array
     */
    public function uploadDataProvider(): array
    {
        return [
            'no optionnal parameter'   => [],
            'type'                     => [new AttachmentType()],
            'companyOwner'             => [null, new Companies()],
            'description'              => [null, null, Base::randomLetter()],
            'all optionnal parameters' => [new AttachmentType(), new Companies(), Base::randomLetter()],
        ];
    }

    /**
     * @covers ::logDownload
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testLogDownload(): void
    {
        $attachment = new Attachment('test', new Clients());

        $attachmentManager = $this->createTestObject();

        $attachmentManager->logDownload($attachment);

        static::assertInstanceOf(DateTimeInterface::class, $attachment->getDownloaded());
        $this->attachmentRepository->save(Argument::exact($attachment))->shouldHaveBeenCalled();
    }

    /**
     * @covers ::read
     *
     * @throws FileNotFoundException
     */
    public function testRead(): void
    {
        $attachment = new Attachment('test', new Clients());

        $attachmentManager = $this->createTestObject();
        $attachmentManager->read($attachment);

        $this->userAttachmentFilesystem->read(Argument::exact($attachment->getPath()))->shouldHaveBeenCalled();
    }

    /**
     * @covers ::getFilesystem
     */
    public function testGetFilesystem(): void
    {
        $attachmentManager = $this->createTestObject();
        /** @var ProphecySubjectInterface $fileSystem */
        $fileSystem = $attachmentManager->getFileSystem();

        // the call to getProphecy is necessary as $filesystem is a test double
        static::assertSame($this->userAttachmentFilesystem, $fileSystem->getProphecy());
    }

    /**
     * @return AttachmentManager
     */
    protected function createTestObject(): AttachmentManager
    {
        return new AttachmentManager(
            $this->userAttachmentFilesystem->reveal(),
            $this->fileUploadManager->reveal(),
            $this->attachmentRepository->reveal()
        );
    }
}
