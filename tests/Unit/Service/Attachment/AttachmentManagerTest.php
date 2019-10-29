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
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, AttachmentType, Clients, Companies};
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
     * @dataProvider uploadProvider
     *
     * @param Clients|null        $owner
     * @param Companies|null      $company
     * @param Clients             $uploader
     * @param AttachmentType|null $attachmentType
     * @param Attachment|null     $existingAttachment
     * @param UploadedFile        $uploadedFile
     * @param bool                $archivePreviousAttachments
     * @param string|null         $description
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testUpload(
        ?Clients $owner,
        ?Companies $company,
        Clients $uploader,
        ?AttachmentType $attachmentType,
        ?Attachment $existingAttachment,
        UploadedFile $uploadedFile,
        bool $archivePreviousAttachments = true,
        ?string $description = null
    ): void {
        $this->fileUploadManager->uploadFile(Argument::type(UploadedFile::class), Argument::cetera())->will(
            static function ($args) {
                return $args[3];
            }
        );
        $this->attachmentRepository->save(Argument::type(Attachment::class));
        $attachmentManager = $this->createTestObject();

        $createdAttachment = $attachmentManager->upload(
            $owner,
            $company,
            $uploader,
            $attachmentType,
            $existingAttachment,
            $uploadedFile,
            $archivePreviousAttachments,
            $description
        );

        static::assertSame($owner, $createdAttachment->getClientOwner());
        static::assertSame($company, $createdAttachment->getCompanyOwner());
        static::assertSame($attachmentType, $createdAttachment->getType());
        static::assertSame($this->realUser, $createdAttachment->getAddedBy());
        if ($owner) {
            static::assertStringContainsString((string) $owner->getIdClient(), $createdAttachment->getPath());
        } else {
            static::assertStringContainsString((string) $uploader->getIdClient(), $createdAttachment->getPath());
        }
        static::assertSame($description, $createdAttachment->getDescription());
        $this->attachmentRepository->save(Argument::type(Attachment::class))->shouldHaveBeenCalled();
    }

    /**
     * @throws ReflectionException
     *
     * @return array
     */
    public function uploadProvider(): array
    {
        $idClientsReflectionProperty = new ReflectionProperty(Clients::class, 'idClient');
        $idClientsReflectionProperty->setAccessible(true);
        $owner   = new Clients();
        $ownerId = Base::randomDigitNotNull();
        $idClientsReflectionProperty->setValue($owner, $ownerId);

        $company = new Companies('CALS', '850890666');

        $uploader   = new Clients();
        $uploaderId = $ownerId + 1;
        $idClientsReflectionProperty->setValue($uploader, $uploaderId);

        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.php';
        fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, Base::asciify(str_repeat('*', 20)), null, null, true);

        $description = Base::asciify(str_repeat('*', 20));

        $attachmentType = new AttachmentType();

        $attachment = new Attachment();

        return [
            'mandatory' => [
                null, null, $uploader, null, null, $uploadedFile,
            ],
            'archive' => [
                null, null, $uploader, null, null, $uploadedFile, true,
            ],
            'description' => [
                null, null, $uploader, null, null, $uploadedFile, false, $description,
            ],
            'attachmentType' => [
                null, null, $uploader, $attachmentType, null, $uploadedFile,
            ],
            'owner' => [
                $owner, null, $uploader, null, null, $uploadedFile,
            ],
            'company' => [
                null, $company, $uploader, null, null, $uploadedFile,
            ],
            'updateAttachment' => [
                null, $company, $uploader, null, $attachment, $uploadedFile,
            ],
            'all' => [
                $owner, $company, $uploader, $attachmentType, null, $uploadedFile, false, $description,
            ],
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
        $attachment = new Attachment();

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
        $attachment = new Attachment();
        $attachment->setPath('test');

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
        $attachment        = new Attachment();
        $attachmentManager = $this->createTestObject();

        $attachmentManager->archive($attachment);

        static::assertSame($this->realUser, $attachment->getArchivedBy());
        static::assertInstanceOf(DateTimeInterface::class, $attachment->getArchived());
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
