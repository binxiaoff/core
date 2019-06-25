<?php

namespace Unilend\Service\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\{Attachment, Project, ProjectAttachment};
use Unilend\Repository\{AttachmentSignatureRepository, ProjectAttachmentRepository, ProjectAttachmentTypeRepository, ProjectRepository};

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
    /** @var AttachmentSignatureRepository */
    private $attachmentSignatureRepository;

    /**
     * @param AttachmentManager               $attachmentManager
     * @param ProjectAttachmentRepository     $projectAttachmentRepository
     * @param ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
     * @param ProjectRepository               $projectRepository
     * @param AttachmentSignatureRepository   $attachmentSignatureRepository
     */
    public function __construct(
        AttachmentManager $attachmentManager,
        ProjectAttachmentRepository $projectAttachmentRepository,
        ProjectAttachmentTypeRepository $projectAttachmentTypeRepository,
        ProjectRepository $projectRepository,
        AttachmentSignatureRepository $attachmentSignatureRepository
    ) {
        $this->attachmentManager               = $attachmentManager;
        $this->projectAttachmentRepository     = $projectAttachmentRepository;
        $this->projectAttachmentTypeRepository = $projectAttachmentTypeRepository;
        $this->projectRepository               = $projectRepository;
        $this->attachmentSignatureRepository   = $attachmentSignatureRepository;
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
