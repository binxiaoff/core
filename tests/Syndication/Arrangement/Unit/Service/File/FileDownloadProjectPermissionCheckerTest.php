<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Service\File;

use KLS\Core\Entity\File;
use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\FileVersionSignature;
use KLS\Core\Repository\FileVersionSignatureRepository;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectFileRepository;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Security\Voter\ProjectParticipationVoter;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use KLS\Syndication\Arrangement\Service\File\FileDownloadProjectPermissionChecker;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Arrangement\Unit\Traits\ArrangementProjectSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Service\File\FileDownloadProjectPermissionChecker
 *
 * @internal
 */
class FileDownloadProjectPermissionCheckerTest extends TestCase
{
    use PropertyValueTrait;
    use UserStaffTrait;
    use ArrangementProjectSetTrait;
    use ProphecyTrait;

    /** @var AuthorizationCheckerInterface|ObjectProphecy */
    private $authorizationChecker;

    /** @var FileVersionSignatureRepository|ObjectProphecy */
    private $fileVersionSignatureRepository;

    /** @var ProjectFileRepository|ObjectProphecy */
    private $projectFileRepository;

    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;

    /** @var ProjectParticipationRepository|ObjectProphecy */
    private $projectParticipationRepository;

    protected function setUp(): void
    {
        $this->authorizationChecker           = $this->prophesize(AuthorizationCheckerInterface::class);
        $this->fileVersionSignatureRepository = $this->prophesize(FileVersionSignatureRepository::class);
        $this->projectFileRepository          = $this->prophesize(ProjectFileRepository::class);
        $this->projectRepository              = $this->prophesize(ProjectRepository::class);
        $this->projectParticipationRepository = $this->prophesize(ProjectParticipationRepository::class);
    }

    protected function tearDown(): void
    {
        $this->authorizationChecker           = null;
        $this->fileVersionSignatureRepository = null;
        $this->projectFileRepository          = null;
        $this->projectRepository              = null;
        $this->projectParticipationRepository = null;
    }

    /**
     * @covers ::check
     *
     * @dataProvider projectFileTypeProvider
     */
    public function testCheckWithProjectFileType(string $type): void
    {
        $staff                = $this->createStaff();
        $user                 = $staff->getUser();
        $file                 = new File();
        $fileVersion          = new FileVersion('', $user, $file, '');
        $fileVersionSignature = new FileVersionSignature($fileVersion, $staff, $staff);
        $fileDownload         = new FileDownload($fileVersion, $user, $type);
        $project              = $this->createArrangementProject($staff);
        $projectFile          = new ProjectFile($type, $file, $project, $staff);

        $this->forcePropertyValue($file, 'id', 1);
        $this->forcePropertyValue($fileVersion, 'id', 1);

        $this->projectFileRepository->findOneBy(['file' => $file, 'type' => $type])->shouldBeCalledOnce()->willReturn($projectFile);
        $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->projectParticipationRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->fileVersionSignatureRepository->findOneBy([
            'fileVersion' => $fileDownload->getFileVersion(),
            'signatory'   => $staff,
        ])->shouldBeCalledOnce()->willReturn($fileVersionSignature);
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertTrue($result);
    }

    /**
     * @covers ::check
     *
     * @dataProvider projectFileTypeProvider
     */
    public function testCheckWithoutProjectFileType(string $type): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, $type);

        $this->forcePropertyValue($file, 'id', 1);

        $this->projectFileRepository->findOneBy(['file' => $file, 'type' => $type])->shouldBeCalledOnce()->willReturn(null);
        $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->projectParticipationRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->fileVersionSignatureRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->authorizationChecker->isGranted(Argument::cetera())->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    public function projectFileTypeProvider(): iterable
    {
        foreach (ProjectFile::getProjectFileTypes() as $type) {
            yield $type => [$type];
        }
    }

    /**
     * @covers ::check
     *
     * @dataProvider projectTypeProvider
     */
    public function testCheckWithProjectType(string $type): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, $type);
        $project      = $this->createArrangementProject($staff);

        $this->forcePropertyValue($file, 'id', 1);
        $this->forcePropertyValue($fileVersion, 'id', 1);

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        if (Project::PROJECT_FILE_TYPE_DESCRIPTION === $type) {
            $this->projectRepository->findOneBy(['termSheet' => $file])->shouldBeCalledOnce()->willReturn($project);
            $this->projectRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        } elseif (Project::PROJECT_FILE_TYPE_NDA === $type) {
            $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
            $this->projectRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn($project);
        }

        $this->projectParticipationRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->fileVersionSignatureRepository->findOneBy([
            'fileVersion' => $fileDownload->getFileVersion(),
            'signatory'   => $staff,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldBeCalledOnce()->willReturn(true);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertTrue($result);
    }

    public function projectTypeProvider(): iterable
    {
        foreach (Project::getProjectFileTypes() as $type) {
            yield $type => [$type];
        }
    }

    /**
     * @covers ::check
     */
    public function testCheckWithProjectTypeNdaWithoutProject(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Project::PROJECT_FILE_TYPE_NDA);
        $project      = $this->createArrangementProject($staff);

        $this->forcePropertyValue($file, 'id', 1);
        $this->forcePropertyValue($fileVersion, 'id', 1);

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn(null);
        $this->projectParticipationRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn(null);
        $this->fileVersionSignatureRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    /**
     * @covers ::check
     */
    public function testCheckWithProjectParticipationType(): void
    {
        $staff                = $this->createStaff();
        $user                 = $staff->getUser();
        $file                 = new File();
        $fileVersion          = new FileVersion('', $user, $file, '');
        $fileDownload         = new FileDownload($fileVersion, $user, ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA);
        $project              = $this->createArrangementProject($staff);
        $projectParticipation = $this->createProjectParticipation($staff, $project);

        $this->forcePropertyValue($file, 'id', 1);
        $this->forcePropertyValue($fileVersion, 'id', 1);

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->projectParticipationRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn($projectParticipation);
        $this->fileVersionSignatureRepository->findOneBy([
            'fileVersion' => $fileDownload->getFileVersion(),
            'signatory'   => $staff,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldBeCalledOnce()->willReturn(false);
        $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $project->getArrangerProjectParticipation())->shouldBeCalledOnce()->willReturn(true);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertTrue($result);
    }

    /**
     * @covers ::check
     *
     * @dataProvider projectStatusProvider
     */
    public function testCheckWithProjectParticipationTypeAndProjectStatus(int $status): void
    {
        $staff                = $this->createStaff();
        $user                 = $staff->getUser();
        $file                 = new File();
        $fileVersion          = new FileVersion('', $user, $file, '');
        $fileDownload         = new FileDownload($fileVersion, $user, ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA);
        $project              = $this->createArrangementProject($staff, $status);
        $projectParticipation = $this->createProjectParticipation($staff, $project);

        $this->forcePropertyValue($file, 'id', 1);
        $this->forcePropertyValue($fileVersion, 'id', 1);

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->projectParticipationRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn($projectParticipation);
        $this->fileVersionSignatureRepository->findOneBy([
            'fileVersion' => $fileDownload->getFileVersion(),
            'signatory'   => $staff,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldBeCalledOnce()->willReturn(false);
        $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $project->getArrangerProjectParticipation())->shouldBeCalledOnce()->willReturn(false);
        $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()])->shouldBeCalledOnce()->willReturn($projectParticipation);
        $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $projectParticipation)->shouldBeCalledOnce()->willReturn(true);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertTrue($result);
    }

    public function projectStatusProvider(): iterable
    {
        yield ProjectStatus::STATUS_INTEREST_EXPRESSION => [ProjectStatus::STATUS_INTEREST_EXPRESSION];
        yield ProjectStatus::STATUS_PARTICIPANT_REPLY => [ProjectStatus::STATUS_PARTICIPANT_REPLY];
    }

    /**
     * @covers ::check
     *
     * @dataProvider projectStatusWithoutProjectParticipationProvider
     */
    public function testCheckWithProjectParticipationTypeAndProjectStatusWithoutProjectParticipation(int $status): void
    {
        $staff                = $this->createStaff();
        $user                 = $staff->getUser();
        $file                 = new File();
        $fileVersion          = new FileVersion('', $user, $file, '');
        $fileDownload         = new FileDownload($fileVersion, $user, ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA);
        $project              = $this->createArrangementProject($staff, $status);
        $projectParticipation = $this->createProjectParticipation($staff, $project);

        $this->forcePropertyValue($file, 'id', 1);
        $this->forcePropertyValue($fileVersion, 'id', 1);

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['termSheet' => Argument::any()])->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => Argument::any()])->shouldNotBeCalled();
        $this->projectParticipationRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn($projectParticipation);
        $this->fileVersionSignatureRepository->findOneBy([
            'fileVersion' => $fileDownload->getFileVersion(),
            'signatory'   => $staff,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldBeCalledOnce()->willReturn(false);
        $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $project->getArrangerProjectParticipation())->shouldBeCalledOnce()->willReturn(false);
        $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()])->shouldBeCalledOnce()->willReturn(null);
        $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $projectParticipation)->willReturn(true);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    public function projectStatusWithoutProjectParticipationProvider(): iterable
    {
        yield ProjectStatus::STATUS_ALLOCATION => [ProjectStatus::STATUS_ALLOCATION];
        yield ProjectStatus::STATUS_CONTRACTUALISATION => [ProjectStatus::STATUS_CONTRACTUALISATION];
        yield ProjectStatus::STATUS_SYNDICATION_FINISHED => [ProjectStatus::STATUS_SYNDICATION_FINISHED];
        yield ProjectStatus::STATUS_SYNDICATION_CANCELLED => [ProjectStatus::STATUS_SYNDICATION_CANCELLED];
    }

    private function createTestObject(): FileDownloadProjectPermissionChecker
    {
        return new FileDownloadProjectPermissionChecker(
            $this->authorizationChecker->reveal(),
            $this->fileVersionSignatureRepository->reveal(),
            $this->projectFileRepository->reveal(),
            $this->projectRepository->reveal(),
            $this->projectParticipationRepository->reveal()
        );
    }
}
