<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Faker\Provider\{Base, Internet, Miscellaneous};
use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\{ObjectProphecy};
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, Clients, Companies, Embeddable\Money, Project};
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\{Attachment\AttachmentManager, FileSystem\FileUploadManager};

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
     * @param string       $type
     * @param Project|null $project
     * @param string|null  $description
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReflectionException
     * @throws Exception
     */
    public function testUpload(
        string $type,
        Project $project = null,
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
        $owner   = new Clients('test@' . Internet::safeEmailDomain());
        $ownerId = Base::randomDigitNotNull();
        $idClientsReflectionProperty->setValue($owner, $ownerId);

        $uploader   = new Clients('test@' . Internet::safeEmailDomain());
        $uploaderId = Base::randomDigitNotNull() + 1;
        $idClientsReflectionProperty->setValue($uploader, $uploaderId);

        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, Base::asciify(str_repeat('*', 20)), null, null, true);

        $createdAttachment = $attachmentManager->upload(
            $uploadedFile,
            $uploader,
            $type,
            $project,
            $description
        );

        static::assertSame($uploader, $createdAttachment->getAddedBy());
        static::assertStringContainsString((string) $uploader->getIdClient(), $createdAttachment->getPath());
        static::assertSame($type, $createdAttachment->getType());
        static::assertSame($project, $createdAttachment->getProject());
        static::assertSame($description, $createdAttachment->getDescription());
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function uploadDataProvider(): array
    {
        $project = $this->createProject();

        return [
            'type and project'        => [Base::randomLetter(), $project],
            'description'             => [Base::randomLetter(), $project, Base::randomLetter()],
            'all optional parameters' => [Base::randomLetter(), $project, Base::randomLetter()],
        ];
    }

    /**
     * @covers ::read
     *
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function testRead(): void
    {
        $attachment = $this->createAttachment();

        $attachmentManager = $this->createTestObject();
        $attachmentManager->read($attachment);

        $this->userAttachmentFilesystem->read(Argument::exact($attachment->getPath()))->shouldHaveBeenCalled();
    }

    /**
     * @throws Exception
     *
     * @return Attachment
     */
    protected function createAttachment(): Attachment
    {
        return new Attachment(
            'test',
            'someType',
            new Clients('test@' . Internet::safeEmailDomain()),
            $this->createProject()
        );
    }

    /**
     * @throws Exception
     *
     * @return Project
     */
    protected function createProject(): Project
    {
        $client = $this->prophesize(Clients::class);
        $client->getCompany()->willReturn(new Companies(Base::lexify('????')));

        return new Project($client->reveal(), new Companies(Base::lexify('????')), new Money(Miscellaneous::currencyCode()));
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
