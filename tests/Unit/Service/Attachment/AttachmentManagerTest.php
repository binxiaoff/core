<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Attachment;

use DateTimeInterface;
use Doctrine\ORM\{EntityManagerInterface, ORMException, OptimisticLockException};
use Exception;
use Faker\Provider\Base;
use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\{ObjectProphecy, ProphecySubjectInterface};
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, Clients};
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\Attachment\AttachmentManager;
use Unilend\Service\FileSystem\FileUploadManager;
use Unilend\Service\User\RealUserFinder;

/**
 * @coversDefaultClass \Unilend\Service\Attachment\AttachmentManager
 *
 * @internal
 */
class AttachmentManagerTest extends TestCase
{
    /** @var EntityManagerInterface|ObjectProphecy */
    private $entityManager;

    /** @var FilesystemInterface|ObjectProphecy */
    private $userAttachmentFilesystem;

    /** @var FileUploadManager|ObjectProphecy */
    private $fileUploadManager;

    /** @var RealUserFinder|ObjectProphecy */
    private $realUserFinder;

    /** @var AttachmentRepository|ObjectProphecy */
    private $attachmentRepository;
    /**
     * @var Clients
     */
    private $realUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->entityManager            = $this->prophesize(EntityManagerInterface::class);
        $this->userAttachmentFilesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileUploadManager        = $this->prophesize(FileUploadManager::class);
        $this->realUserFinder           = $this->prophesize(RealUserFinder::class);
        $this->realUser                 = new Clients();
        $this->realUserFinder->__invoke()->willReturn($this->realUser);
        $this->attachmentRepository = $this->prophesize(AttachmentRepository::class);
    }

    /**
     * @covers ::upload
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testUpload(): void
    {
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
            $uploader
        );

        static::assertSame($uploader, $createdAttachment->getAddedBy());
        static::assertStringContainsString((string) $uploader->getIdClient(), $createdAttachment->getPath());
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
     * @covers ::archive
     *
     * @throws Exception
     */
    public function testArchive(): void
    {
        $attachment        = new Attachment('test', $this->realUser);
        $attachmentManager = $this->createTestObject();

        $attachmentManager->archive($attachment);

        static::assertSame($this->realUser, $attachment->getArchivedBy());
        static::assertInstanceOf(DateTimeInterface::class, $attachment->getArchivedAt());
        $this->attachmentRepository->save(Argument::exact($attachment))->shouldHaveBeenCalled();
    }

    /**
     * @return AttachmentManager
     */
    protected function createTestObject(): AttachmentManager
    {
        return new AttachmentManager(
            $this->entityManager->reveal(),
            $this->userAttachmentFilesystem->reveal(),
            $this->fileUploadManager->reveal(),
            $this->realUserFinder->reveal(),
            $this->attachmentRepository->reveal()
        );
    }
}
