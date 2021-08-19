<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Service\FileInput;

use KLS\Core\Entity\File;
use KLS\Core\Exception\File\DenyUploadExistingFileException;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Syndication\Arrangement\Entity\Project as ArrangementProject;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectFileRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Security\Voter\ProjectFileVoter;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use KLS\Syndication\Arrangement\Service\FileInput\FileInputProjectUploader;
use KLS\Test\Core\Unit\Traits\FileInputEntitiesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Service\FileInput\FileInputProjectUploader
 *
 * @internal
 */
class FileInputProjectUploaderTest extends TestCase
{
    use FileInputEntitiesTrait;

    /** @var Security|ObjectProphecy */
    private $security;
    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;
    /** @var ProjectFileRepository|ObjectProphecy */
    private $projectFileRepository;
    /** @var FileUploadManager|ObjectProphecy */
    private $fileUploadManager;

    protected function setUp(): void
    {
        $this->security              = $this->prophesize(Security::class);
        $this->projectRepository     = $this->prophesize(ProjectRepository::class);
        $this->projectFileRepository = $this->prophesize(ProjectFileRepository::class);
        $this->fileUploadManager     = $this->prophesize(FileUploadManager::class);
    }

    protected function tearDown(): void
    {
        $this->security              = null;
        $this->projectRepository     = null;
        $this->projectFileRepository = null;
        $this->fileUploadManager     = null;
    }

    /**
     * @covers ::supports
     */
    public function testSupports(): void
    {
        $targetEntity = $this->createArrangementProject($this->createStaff());

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
        yield 'projectParticipation' => [$this->createProjectParticipation($staff, $this->createArrangementProject($staff))];
    }

    /**
     * @covers ::upload
     */
    public function testUploadProjectFileTypeWithoutFile(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $token        = $this->createToken($staff, ['staff' => $staff, 'company' => $staff->getCompany()]);
        $fileInput    = $this->createFileInput($targetEntity, ProjectFile::PROJECT_FILE_TYPE_GENERAL);
        $user         = $staff->getUser();

        // uploadForProjectFile
        $this->security->getToken()->shouldBeCalledTimes(2)->willReturn($token);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, Argument::type(ProjectFile::class))->shouldBeCalledOnce()->willReturn(true);
        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, Argument::type(ProjectFile::class))->shouldNotBeCalled();
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, Argument::type(File::class), ['projectId' => $targetEntity->getId()], $staff->getCompany())
            ->shouldBeCalledOnce()
        ;
        $this->projectFileRepository->save(Argument::type(ProjectFile::class))->shouldBeCalledOnce();

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, null);

        static::assertInstanceOf(File::class, $result);
    }

    /**
     * @covers ::upload
     */
    public function testUploadProjectFileTypeWithoutFileAccessDenied(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $token        = $this->createToken($staff, ['staff' => $staff]);
        $fileInput    = $this->createFileInput($targetEntity, ProjectFile::PROJECT_FILE_TYPE_GENERAL);
        $user         = $staff->getUser();

        // uploadForProjectFile
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, Argument::type(ProjectFile::class))->shouldBeCalledOnce()->willReturn(false);
        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, Argument::type(ProjectFile::class))->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();
        $this->projectFileRepository->save(Argument::any())->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, null);
    }

    /**
     * @covers ::upload
     */
    public function testUploadProjectFileTypeWithFile(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $token        = $this->createToken($staff, ['staff' => $staff, 'company' => $staff->getCompany()]);
        $fileInput    = $this->createFileInput($targetEntity, ProjectFile::PROJECT_FILE_TYPE_GENERAL);
        $file         = new File();
        $projectFile  = new ProjectFile($fileInput->type, $file, $targetEntity, $staff);
        $user         = $staff->getUser();

        // uploadForProjectFile
        $this->security->getToken()->shouldBeCalledTimes(2)->willReturn($token);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, $projectFile)->shouldNotBeCalled();
        $this->projectFileRepository->findOneBy(['file' => $file, 'project' => $targetEntity, 'type' => $fileInput->type])->shouldBeCalledOnce()->willReturn($projectFile);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, $projectFile)->shouldBeCalledOnce()->willReturn(true);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, ['projectId' => $targetEntity->getId()], $staff->getCompany())->shouldBeCalledOnce();
        $this->projectFileRepository->save($projectFile)->shouldBeCalledOnce();

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, $file);

        static::assertInstanceOf(File::class, $result);
    }

    /**
     * @covers ::upload
     */
    public function testUploadProjectFileTypeWithFileAccessDenied(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $token        = $this->createToken($staff, ['staff' => $staff]);
        $fileInput    = $this->createFileInput($targetEntity, ProjectFile::PROJECT_FILE_TYPE_GENERAL);
        $file         = new File();
        $projectFile  = new ProjectFile($fileInput->type, $file, $targetEntity, $staff);
        $user         = $staff->getUser();

        // uploadForProjectFile
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, $projectFile)->shouldNotBeCalled();
        $this->projectFileRepository->findOneBy(['file' => $file, 'project' => $targetEntity, 'type' => $fileInput->type])->shouldBeCalledOnce()->willReturn($projectFile);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, $projectFile)->shouldBeCalledOnce()->willReturn(false);
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();
        $this->projectFileRepository->save(Argument::any())->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    /**
     * @covers ::upload
     */
    public function testUploadProjectFileTypeWithFileException(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $token        = $this->createToken($staff, ['staff' => $staff]);
        $fileInput    = $this->createFileInput($targetEntity, ProjectFile::PROJECT_FILE_TYPE_GENERAL);
        $file         = new File();
        $user         = $staff->getUser();

        $targetEntity->setPublicId();
        $file->setPublicId();

        // uploadForProjectFile
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, Argument::type(ProjectFile::class))->shouldNotBeCalled();
        $this->projectFileRepository->findOneBy(['file' => $file, 'project' => $targetEntity, 'type' => $fileInput->type])->shouldBeCalledOnce()->willReturn(null);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, Argument::type(ProjectFile::class))->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();
        $this->projectFileRepository->save(Argument::any())->shouldNotBeCalled();

        static::expectException(RuntimeException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    /**
     * @covers ::upload
     *
     * @dataProvider projectTypeProvider
     */
    public function testUploadProjectType(string $type, ?File $file = null): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $token        = $this->createToken($staff, ['company' => $staff->getCompany()]);
        $fileInput    = $this->createFileInput($targetEntity, $type);
        $user         = $staff->getUser();

        // uploadForProject
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity)->shouldBeCalledOnce()->willReturn(true);
        $this->security->getToken()->shouldBeCalledOnce()->willReturn($token);
        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, Argument::type(File::class), ['projectId' => $targetEntity->getId()], $staff->getCompany())
            ->shouldBeCalledOnce()
        ;
        $this->projectRepository->save($targetEntity)->shouldBeCalledOnce();

        $uploader = $this->createTestObject();
        $result   = $uploader->upload($targetEntity, $fileInput, $user, $file);

        static::assertInstanceOf(File::class, $result);
    }

    public function projectTypeProvider(): iterable
    {
        yield ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION . ' and no file' => [
            ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION,
            null,
        ];
        yield ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION . ' and file' => [
            ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION,
            new File(),
        ];
        yield ArrangementProject::PROJECT_FILE_TYPE_NDA . ' and no file' => [
            ArrangementProject::PROJECT_FILE_TYPE_NDA,
            null,
        ];
        yield ArrangementProject::PROJECT_FILE_TYPE_NDA . ' and file' => [
            ArrangementProject::PROJECT_FILE_TYPE_NDA,
            new File(),
        ];
    }

    /**
     * @covers ::upload
     */
    public function testUploadProjectTypeAccessDenied(): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff);
        $fileInput    = $this->createFileInput($targetEntity, ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION);
        $user         = $staff->getUser();

        // uploadForProject
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity)->shouldBeCalledOnce()->willReturn(false);
        $this->security->getToken()->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();
        $this->projectRepository->save(Argument::any())->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, null);
    }

    /**
     * @covers ::upload
     *
     * @dataProvider deniedWithExistingFileProvider
     */
    public function testUploadProjectTypeDescriptionDeniedWithExistingFile(string $type): void
    {
        $staff        = $this->createStaff();
        $targetEntity = $this->createArrangementProject($staff, ProjectStatus::STATUS_INTEREST_EXPRESSION);
        $fileInput    = $this->createFileInput($targetEntity, $type);
        $file         = new File();
        $user         = $staff->getUser();

        $targetEntity->setPublicId();

        if (ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION === $type) {
            $targetEntity->setTermSheet((new File())->setPublicId());
        }
        if (ArrangementProject::PROJECT_FILE_TYPE_NDA === $type) {
            $targetEntity->setNda((new File())->setPublicId());
        }

        // uploadForProject
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity)->shouldBeCalledOnce()->willReturn(true);
        $this->security->getToken()->shouldNotBeCalled();
        $this->fileUploadManager->upload(Argument::cetera())->shouldNotBeCalled();
        $this->projectRepository->save(Argument::any())->shouldNotBeCalled();

        static::expectException(DenyUploadExistingFileException::class);

        $uploader = $this->createTestObject();
        $uploader->upload($targetEntity, $fileInput, $user, $file);
    }

    public function deniedWithExistingFileProvider(): iterable
    {
        yield ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION => [ArrangementProject::PROJECT_FILE_TYPE_DESCRIPTION];
        yield ArrangementProject::PROJECT_FILE_TYPE_NDA => [ArrangementProject::PROJECT_FILE_TYPE_NDA];
    }

    private function createTestObject(): FileInputProjectUploader
    {
        return new FileInputProjectUploader(
            $this->security->reveal(),
            $this->projectRepository->reveal(),
            $this->projectFileRepository->reveal(),
            $this->fileUploadManager->reveal()
        );
    }
}
