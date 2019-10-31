<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Entity\{Attachment, AttachmentType, Clients, Companies, Project, ProjectAttachment, ProjectAttachmentType};
use Unilend\Repository\{ProjectAttachmentRepository, ProjectAttachmentTypeRepository, ProjectRepository};
use Unilend\Service\Attachment\{AttachmentManager, ProjectAttachmentManager};

/**
 * Class ProjectAttachmentTest.
 *
 * @coversDefaultClass  \Unilend\Service\Attachment\ProjectAttachmentManager
 *
 * @internal
 */
class ProjectAttachmentManagerTest extends TestCase
{
    /**
     * @var ProjectAttachmentRepository|ObjectProphecy
     */
    private $projectAttachmentRepository;
    /**
     * @var AttachmentManager|ObjectProphecy
     */
    private $attachmentManager;
    /**
     * @var ProjectRepository|ObjectProphecy
     */
    private $projectRepository;
    /**
     * @var ProjectAttachmentRepository|ObjectProphecy
     */
    private $projectAttachmentTypeRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->attachmentManager = $this->prophesize(AttachmentManager::class);

        $this->projectRepository = $this->prophesize(ProjectRepository::class);

        $this->projectAttachmentRepository = $this->prophesize(ProjectAttachmentRepository::class);

        $this->projectAttachmentTypeRepository = $this->prophesize(ProjectAttachmentTypeRepository::class);
    }

    /**
     * @covers ::attachToProject
     *
     * @throws Exception
     */
    public function testAttachToProject(): void
    {
        $project    = $this->createProject();
        $attachment = new Attachment('test', new Clients());

        $this->projectAttachmentRepository
            ->getAttachedAttachmentsByType(Argument::exact($project), Argument::cetera())
            ->willReturn([])
        ;
        $this->projectAttachmentRepository
            ->findOneBy(Argument::any())
            ->willReturn(null)
        ;

        $projectAttachmentManager = $this->createTestObject();

        $projectAttachment = $projectAttachmentManager->attachToProject($attachment, $project);

        $this->projectRepository->save(Argument::exact($project))->shouldHaveBeenCalled();
        static::assertSame($project, $projectAttachment->getProject());
        static::assertSame($attachment, $projectAttachment->getAttachment());
    }

    /**
     * @covers ::attachToProject
     *
     * @throws Exception
     */
    public function testAttachExistingToProject(): void
    {
        $existingProjectAttachment = $this->createProjectAttachment();
        $this->projectAttachmentRepository
            ->getAttachedAttachmentsByType(Argument::exact($existingProjectAttachment->getProject()), Argument::cetera())
            ->willReturn([])
        ;
        $this->projectAttachmentRepository
            ->findOneBy(Argument::any())
            ->willReturn($existingProjectAttachment)
        ;

        $projectAttachmentManager = $this->createTestObject();

        $createdProjectAttachment = $projectAttachmentManager->attachToProject($existingProjectAttachment->getAttachment(), $existingProjectAttachment->getProject());

        $this->projectRepository->save(Argument::exact($existingProjectAttachment->getProject()))->shouldNotHaveBeenCalled();
        $this->projectRepository->save(Argument::exact($createdProjectAttachment->getProject()))->shouldNotHaveBeenCalled();

        static::assertSame($createdProjectAttachment, $existingProjectAttachment);
    }

    /**
     * @covers ::attachToProject
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAttachToProjectWhenAtTheLimit(): void
    {
        $attachmentType        = new AttachmentType();
        $projectAttachmentType = $this->prophesize(ProjectAttachmentType::class);
        $projectAttachmentType->getMaxItems()->willReturn(2);

        $projectAttachmentType->setAttachmentType($attachmentType);

        $project    = $this->createProject();
        $attachment = new Attachment('test', new Clients());
        $attachment->setType($attachmentType);

        $existingProjectAttachment = $this->createProjectAttachment($project);

        $this->projectAttachmentRepository
            ->getAttachedAttachmentsByType(Argument::exact($project), Argument::exact($attachmentType))
            ->willReturn([
                $existingProjectAttachment,
                $this->createProjectAttachment($project),
            ])
        ;

        $this->projectAttachmentRepository
            ->findOneBy(Argument::any())
            ->willReturn(null)
        ;
        $this->projectAttachmentTypeRepository
            ->findOneBy(Argument::any())
            ->willReturn($projectAttachmentType)
        ;

        $this->attachmentManager->isOrphan(Argument::any())->willReturn(false);

        static::assertCount(2, $project->getProjectAttachments(), 'before call');

        $projectAttachmentManager = $this->createTestObject();
        $createdProjectAttachment = $projectAttachmentManager->attachToProject($attachment, $project);

        $projectAttachmentType->getMaxItems()->shouldHaveBeenCalled();
        static::assertSame($createdProjectAttachment->getProject(), $project);
        static::assertCount(2, $project->getProjectAttachments(), 'after call');
    }

    /**
     * @covers ::detachFromProject
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testDetachFromProject(): void
    {
        $projectAttachment = $this->createProjectAttachment();
        $project           = $projectAttachment->getProject();

        $this->attachmentManager->isOrphan(Argument::exact($projectAttachment->getAttachment()))->willReturn(false);
        $this->attachmentManager->archive(Argument::any())->shouldNotBeCalled();

        $projectAttachmentManager = $this->createTestObject();

        $projectAttachmentManager->detachFromProject($projectAttachment);

        $this->projectRepository->save(Argument::exact($project))->shouldHaveBeenCalled();

        static::assertNotContains($projectAttachment, $project->getProjectAttachments());
    }

    /**
     * @covers ::detachFromProject
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testDetachOrphanFromProject(): void
    {
        $projectAttachment = $this->createProjectAttachment();
        $attachment        = $projectAttachment->getAttachment();
        $this->attachmentManager->isOrphan(Argument::exact($attachment))->willReturn(true);
        $this->attachmentManager->archive(Argument::exact($attachment));

        $projectAttachmentManager = $this->createTestObject();

        $projectAttachmentManager->detachFromProject($projectAttachment);
        $this->attachmentManager->archive(Argument::exact($attachment))->shouldHaveBeenCalled();
    }

    /**
     * @return ProjectAttachmentManager
     */
    protected function createTestObject(): ProjectAttachmentManager
    {
        return new ProjectAttachmentManager(
            $this->attachmentManager->reveal(),
            $this->projectRepository->reveal(),
            $this->projectAttachmentRepository->reveal(),
            $this->projectAttachmentTypeRepository->reveal()
        );
    }

    /**
     * @param Project|null    $project
     * @param Attachment|null $attachment
     *
     * @return ProjectAttachment
     */
    protected function createProjectAttachment(?Project $project = null, ?Attachment $attachment = null): ProjectAttachment
    {
        $project    = $project    ?? $this->createProject();
        $attachment = $attachment ?? new Attachment('test', new Clients());

        $projectAttachment = new ProjectAttachment($project, $attachment);

        $project->addProjectAttachment($projectAttachment);

        return $projectAttachment;
    }

    /**
     * @return Project
     */
    protected function createProject(): Project
    {
        return new Project(new Clients(), new Companies('test'));
    }
}
