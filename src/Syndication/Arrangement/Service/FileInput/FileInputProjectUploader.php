<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\FileInput;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\DataTransformer\FileInputDataUploadInterface;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Core\Service\FileInput\FileInputDataUploadTrait;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Repository\ProjectFileRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Security\Voter\ProjectFileVoter;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use League\Flysystem\FilesystemException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class FileInputProjectUploader implements FileInputDataUploadInterface
{
    use FileInputDataUploadTrait;

    private Security $security;
    private ProjectRepository $projectRepository;
    private ProjectFileRepository $projectFileRepository;
    private FileUploadManager $fileUploadManager;

    public function __construct(
        Security $security,
        ProjectRepository $projectRepository,
        ProjectFileRepository $projectFileRepository,
        FileUploadManager $fileUploadManager
    ) {
        $this->security              = $security;
        $this->projectRepository     = $projectRepository;
        $this->projectFileRepository = $projectFileRepository;
        $this->fileUploadManager     = $fileUploadManager;
    }

    public function supports($targetEntity): bool
    {
        return $targetEntity instanceof Project;
    }

    /**
     * @param Project $targetEntity
     *
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FilesystemException
     */
    public function upload($targetEntity, FileInput $fileInput, User $user, ?File $file): File
    {
        $type = $fileInput->type;

        if (\in_array($type, ProjectFile::getProjectFileTypes(), true)) {
            $file = $this->uploadForProjectFile($targetEntity, $fileInput, $user, $file);
        }

        if (\in_array($type, Project::getProjectFileTypes(), true)) {
            $file = $this->uploadForProject($targetEntity, $fileInput, $user, $file);
        }

        return $file;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function uploadForProjectFile(Project $project, FileInput $fileInput, User $user, ?File $file): File
    {
        $currentStaff = $this->getCurrentStaff();

        if (false === ($currentStaff instanceof Staff)) {
            throw new AccessDeniedException(\sprintf('Cannot add new project file if there is no staff attached to logged user'));
        }

        if (null === $file) {
            $file        = new File();
            $projectFile = new ProjectFile($fileInput->type, $file, $project, $currentStaff);

            if (false === $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, $projectFile)) {
                throw new AccessDeniedException();
            }
        } else {
            $projectFile = $this->projectFileRepository->findOneBy(['file' => $file, 'project' => $project, 'type' => $fileInput->type]);

            if (null === $projectFile) {
                throw new RuntimeException(\sprintf(
                    'We cannot find the file (%s) for project (%s) of type (%s). Do you tend to upload a new file (instead of updating it) ?',
                    $file->getPublicId(),
                    $project->getPublicId(),
                    $fileInput->type
                ));
            }

            if (false === $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, $projectFile)) {
                throw new AccessDeniedException();
            }
        }

        $this->fileUploadManager->upload(
            $fileInput->uploadedFile,
            $user,
            $file,
            ['projectId' => $projectFile->getProject()->getId()],
            $this->getCurrentCompany()
        );

        $this->projectFileRepository->save($projectFile);

        return $file;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function uploadForProject(Project $project, FileInput $fileInput, User $user, ?File $file): File
    {
        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            throw new AccessDeniedException();
        }

        $isPublished = $project->isPublished();

        switch ($fileInput->type) {
            case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                $termSheet = $project->getTermSheet();
                if ($isPublished && null !== $file && null !== $termSheet && $file !== $termSheet) {
                    static::denyUploadExistingFile($fileInput, $termSheet, $project);
                }
                $file = $isPublished && $termSheet ? $termSheet : new File();
                $project->setTermSheet($file);
                // Orphan removal takes care to remove unused file
                break;

            case Project::PROJECT_FILE_TYPE_NDA:
                $nda = $project->getNda();
                if ($isPublished && null !== $file && null !== $nda && $file !== $nda) {
                    static::denyUploadExistingFile($fileInput, $nda, $project);
                }
                $file = $isPublished && $nda ? $nda : new File();
                $project->setNda($file);
                // Orphan removal takes care to remove unused file
                break;

            default:
                throw new \InvalidArgumentException(\sprintf('You cannot upload the file of the type %s.', $fileInput->type));
        }

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, ['projectId' => $project->getId()], $this->getCurrentCompany());
        $this->projectRepository->save($project);

        return $file;
    }
}
