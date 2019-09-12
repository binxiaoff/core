<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\{Attachment, Project, ProjectAttachment};
use Unilend\Repository\{ProjectAttachmentRepository, ProjectAttachmentTypeRepository, ProjectRepository};

class ProjectAttachmentManager
{
    /** @var ProjectAttachmentRepository */
    private $projectAttachmentRepository;
    /** @var ProjectAttachmentTypeRepository */
    private $projectAttachmentTypeRepository;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var AttachmentManager */
    private $attachmentManager;

    /**
     * @param AttachmentManager               $attachmentManager
     * @param ProjectRepository               $projectRepository
     * @param ProjectAttachmentRepository     $projectAttachmentRepository
     * @param ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
     */
    public function __construct(
        AttachmentManager $attachmentManager,
        ProjectRepository $projectRepository,
        ProjectAttachmentRepository $projectAttachmentRepository,
        ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
    ) {
        $this->attachmentManager               = $attachmentManager;
        $this->projectRepository               = $projectRepository;
        $this->projectAttachmentRepository     = $projectAttachmentRepository;
        $this->projectAttachmentTypeRepository = $projectAttachmentTypeRepository;
    }

    /**
     * @param Attachment $attachment
     * @param Project    $project
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return ProjectAttachment
     */
    public function attachToProject(Attachment $attachment, Project $project): ProjectAttachment
    {
        $attachmentType        = $attachment->getType();
        $attached              = $this->projectAttachmentRepository->getAttachedAttachmentsByType($project, $attachmentType);
        $projectAttachmentType = $this->projectAttachmentTypeRepository->findOneBy(['attachmentType' => $attachmentType]);

        foreach ($attached as $index => $projectAttachmentToDetach) {
            if (null === $projectAttachmentType->getMaxItems() || $index < $projectAttachmentType->getMaxItems() - 1) {
                continue;
            }

            $this->detachFromProject($projectAttachmentToDetach);
        }

        $projectAttachment = $this->projectAttachmentRepository->findOneBy(['attachment' => $attachment, 'project' => $project]);
        if (null === $projectAttachment) {
            $projectAttachment = new ProjectAttachment();
            $projectAttachment->setAttachment($attachment);

            $project->addProjectAttachment($projectAttachment);

            $this->projectRepository->save($project);
        }

        return $projectAttachment;
    }

    /**
     * @param ProjectAttachment $projectAttachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function detachFromProject(ProjectAttachment $projectAttachment): void
    {
        $project    = $projectAttachment->getProject();
        $attachment = $projectAttachment->getAttachment();

        $project->removeProjectAttachment($projectAttachment);

        $this->projectRepository->save($project);

        if ($this->attachmentManager->isOrphan($attachment)) {
            $this->attachmentManager->archive($attachment);
        }
    }
}
