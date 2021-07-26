<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\File;

use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Core\Service\File\FileDeleteManager;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectFile;
use Unilend\Syndication\Repository\ProjectFileRepository;
use Unilend\Syndication\Repository\ProjectRepository;
use Unilend\Syndication\Security\Voter\ProjectFileVoter;
use Unilend\Syndication\Security\Voter\ProjectVoter;

/**
 * @coversDefaultClass \Unilend\Core\Service\File\FileDeleteManager
 *
 * @internal
 */
class FileDeleteManagerTest extends TestCase
{
    /** @var Security|ObjectProphecy */
    private $security;

    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;

    /** @var ProjectFileRepository|ObjectProphecy */
    private $projectFileRepository;

    protected function setUp(): void
    {
        $this->security              = $this->prophesize(Security::class);
        $this->projectRepository     = $this->prophesize(ProjectRepository::class);
        $this->projectFileRepository = $this->prophesize(ProjectFileRepository::class);
    }

    protected function tearDown(): void
    {
        $this->security              = null;
        $this->projectRepository     = null;
        $this->projectFileRepository = null;
    }

    /**
     * @covers ::delete
     *
     * @dataProvider projectFileProvider
     */
    public function testDeleteForProjectFile(File $file, string $type): void
    {
        $staff       = $this->createStaff();
        $project     = $this->createProject($staff);
        $projectFile = new ProjectFile($type, $file, $project, $staff);

        $this->projectFileRepository->findOneBy(['file' => $file])->shouldBeCalledOnce()->willReturn($projectFile);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_DELETE, $projectFile)->shouldBeCalledOnce()->willReturn(true);
        $this->projectFileRepository->remove($projectFile)->shouldBeCalledOnce();
        $this->projectRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldNotBeCalled();
        $this->projectRepository->flush()->shouldNotBeCalled();

        $fileDeleteManager = $this->createTestObject();
        $fileDeleteManager->delete($file, $type);
    }

    /**
     * @throws Exception
     */
    public function projectFileProvider(): array
    {
        $file = new File();
        $file->setPublicId();

        return [
            ProjectFile::PROJECT_FILE_TYPE_GENERAL              => [$file, ProjectFile::PROJECT_FILE_TYPE_GENERAL],
            ProjectFile::PROJECT_FILE_TYPE_ACCOUNTING_FINANCIAL => [$file, ProjectFile::PROJECT_FILE_TYPE_ACCOUNTING_FINANCIAL],
            ProjectFile::PROJECT_FILE_TYPE_LEGAL                => [$file, ProjectFile::PROJECT_FILE_TYPE_LEGAL],
            ProjectFile::PROJECT_FILE_TYPE_KYC                  => [$file, ProjectFile::PROJECT_FILE_TYPE_KYC],
        ];
    }

    /**
     * @covers ::delete
     */
    public function testDeleteForProjectFileExceptionNotFound(): void
    {
        $file = new File();
        $file->setPublicId();

        $this->projectFileRepository->findOneBy(['file' => $file])->shouldBeCalledOnce()->willReturn(null);
        $this->security->isGranted(Argument::cetera())->shouldNotBeCalled();
        $this->projectFileRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->flush()->shouldNotBeCalled();

        static::expectException(NotFoundHttpException::class);

        $fileDeleteManager = $this->createTestObject();
        $fileDeleteManager->delete($file, ProjectFile::PROJECT_FILE_TYPE_GENERAL);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteForProjectFileExceptionAccessDenied(): void
    {
        $type = ProjectFile::PROJECT_FILE_TYPE_GENERAL;
        $file = new File();
        $file->setPublicId();
        $staff       = $this->createStaff();
        $project     = $this->createProject($staff);
        $projectFile = new ProjectFile($type, $file, $project, $staff);

        $this->projectFileRepository->findOneBy(['file' => $file])->shouldBeCalledOnce()->willReturn($projectFile);
        $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_DELETE, $projectFile)->shouldBeCalledOnce()->willReturn(false);
        $this->projectFileRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->flush()->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $fileDeleteManager = $this->createTestObject();
        $fileDeleteManager->delete($file, $type);
    }

    /**
     * @covers ::delete
     *
     * @dataProvider projectProvider
     */
    public function testDeleteProject(File $file, string $type, string $field): void
    {
        $project = $this->createProject($this->createStaff());

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectFileRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy([$field => $file])->shouldBeCalledOnce()->willReturn($project);
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldBeCalledOnce()->willReturn(true);
        $this->projectRepository->flush()->shouldBeCalledOnce();

        $fileDeleteManager = $this->createTestObject();
        $fileDeleteManager->delete($file, $type);
    }

    /**
     * @throws Exception
     */
    public function projectProvider(): array
    {
        $file = new File();
        $file->setPublicId();

        return [
            Project::PROJECT_FILE_TYPE_DESCRIPTION => [$file, Project::PROJECT_FILE_TYPE_DESCRIPTION, 'termSheet'],
            Project::PROJECT_FILE_TYPE_NDA         => [$file, Project::PROJECT_FILE_TYPE_NDA, 'nda'],
        ];
    }

    /**
     * @covers ::delete
     */
    public function testDeleteForProjectExceptionNotFound(): void
    {
        $file = new File();
        $file->setPublicId();

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectFileRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['termSheet' => $file])->shouldBeCalledOnce()->willReturn(null);
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, Argument::any())->shouldNotBeCalled();
        $this->projectRepository->flush()->shouldNotBeCalled();

        static::expectException(NotFoundHttpException::class);

        $fileDeleteManager = $this->createTestObject();
        $fileDeleteManager->delete($file, Project::PROJECT_FILE_TYPE_DESCRIPTION);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteForProjectExceptionAccessDenied(): void
    {
        $type = Project::PROJECT_FILE_TYPE_NDA;
        $file = new File();
        $file->setPublicId();
        $staff   = $this->createStaff();
        $project = $this->createProject($staff);

        $this->projectFileRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->projectFileRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->projectRepository->findOneBy(['nda' => $file])->shouldBeCalledOnce()->willReturn($project);
        $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)->shouldBeCalledOnce()->willReturn(false);
        $this->projectRepository->flush()->shouldNotBeCalled();

        static::expectException(AccessDeniedException::class);

        $fileDeleteManager = $this->createTestObject();
        $fileDeleteManager->delete($file, $type);
    }

    private function createProject(Staff $staff): Project
    {
        return new Project(
            $staff,
            'RISK-GROUP-42',
            new Money('EUR', '42')
        );
    }

    private function createStaff(): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }

    private function createTestObject(): FileDeleteManager
    {
        return new FileDeleteManager(
            $this->security->reveal(),
            $this->projectRepository->reveal(),
            $this->projectFileRepository->reveal(),
        );
    }
}
