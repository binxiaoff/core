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
use Unilend\Entity\{Clients, Company, Embeddable\Money, FileVersion, MarketSegment, Project, Staff};
use Unilend\Repository\FileVersionRepository;
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

    /** @var FileVersionRepository|ObjectProphecy */
    private $fileVersionRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->userAttachmentFilesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileUploadManager        = $this->prophesize(FileUploadManager::class);
        $this->fileVersionRepository    = $this->prophesize(FileVersionRepository::class);
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
        $this->fileUploadManager->uploadFile(Argument::type(UploadedFile::class), Argument::cetera(), '/', null, false)->will(
            function ($args) {
                return [$args[3], null];
            }
        );
        $this->fileVersionRepository->save(Argument::type(FileVersion::class));
        $attachmentManager = $this->createTestObject();

        $idClientsReflectionProperty = new ReflectionProperty(Clients::class, 'id');
        $idClientsReflectionProperty->setAccessible(true);
        $owner   = new Clients('test@' . Internet::safeEmailDomain());
        $ownerId = Base::randomDigitNotNull();
        $idClientsReflectionProperty->setValue($owner, $ownerId);

        $uploader   = new Clients('test@' . Internet::safeEmailDomain());
        $uploaderId = Base::randomDigitNotNull() + 1;
        $idClientsReflectionProperty->setValue($uploader, $uploaderId);
        $uploaderStaff = new Staff(new Company('test'), $uploader, $this->prophesize(Staff::class)->reveal());

        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        fopen($filePath, 'wb+');
        $uploadedFile = new UploadedFile($filePath, Base::asciify(str_repeat('*', 20)), null, null, true);

        $createdAttachment = $attachmentManager->upload(
            $uploadedFile,
            $uploaderStaff,
            $type,
            $project,
            $description
        );

        static::assertSame($uploader, $createdAttachment->getAddedBy()->getClient());
        static::assertStringContainsString((string) $uploader->getId(), $createdAttachment->getPath());
        static::assertSame($type, $createdAttachment->getType());
        //@todo change that
//        static::assertSame($project, $createdAttachment->getProject());
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
     *@throws Exception
     *
     * @return FileVersion
     */
    protected function createAttachment(): FileVersion
    {
        return new FileVersion(
            'test',
            'someType',
            new Staff(
                new Company('test'),
                new Clients('test@' . Internet::safeEmailDomain()),
                $this->prophesize(Staff::class)->reveal()
            ),
            $this->createProject(),
            'key',
            'application/octet-stream'
        );
    }

    /**
     * @throws Exception
     *
     * @return Project
     */
    protected function createProject(): Project
    {
        $company = new Company(Base::lexify('????'));
        $client  = new Clients('test@' . Internet::freeEmailDomain());
        $staff   = new Staff($company, $client, $this->prophesize(Staff::class)->reveal());

        return new Project($staff, $company, new Money(Miscellaneous::currencyCode()), new MarketSegment());
    }

    /**
     * @return AttachmentManager
     */
    protected function createTestObject(): AttachmentManager
    {
        return new AttachmentManager(
            $this->userAttachmentFilesystem->reveal(),
            $this->fileUploadManager->reveal(),
            $this->fileVersionRepository->reveal(),
        );
    }
}
