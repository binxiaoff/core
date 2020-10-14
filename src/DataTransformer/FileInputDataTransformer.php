<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\Validator\ValidatorInterface;
use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, IOException};
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use League\Flysystem\FileExistsException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\{Exception\AccessDeniedException, Security};
use Unilend\DTO\FileInput;
use Unilend\Entity\{Clients,
    File,
    Project,
    ProjectFile,
    ProjectParticipation,
    Staff};
use Unilend\Repository\{ProjectFileRepository, ProjectRepository};
use Unilend\Security\Voter\{ProjectFileVoter, ProjectParticipationVoter, ProjectVoter};
use Unilend\Service\File\FileUploadManager;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class FileInputDataTransformer
{
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;
    /** @var Security */
    private Security $security;
    /** @var FileUploadManager */
    private FileUploadManager $fileUploadManager;
    /** @var ProjectFileRepository */
    private ProjectFileRepository $projectFileRepository;
    /** @var ProjectRepository  */
    private ProjectRepository $projectRepository;
    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param ValidatorInterface          $validator
     * @param Security                    $security
     * @param FileUploadManager           $fileUploadManager
     * @param ProjectParticipationManager $projectParticipationManager
     * @param ProjectFileRepository       $projectFileRepository
     * @param ProjectRepository           $projectRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        Security $security,
        FileUploadManager $fileUploadManager,
        ProjectParticipationManager $projectParticipationManager,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository
    ) {
        $this->validator             = $validator;
        $this->security              = $security;
        $this->fileUploadManager     = $fileUploadManager;
        $this->projectFileRepository = $projectFileRepository;
        $this->projectRepository     = $projectRepository;
        $this->projectParticipationManager = $projectParticipationManager;
    }

    /**
     * @param FileInput $fileInput
     * @param File|null $file
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FileExistsException
     * @throws Exception
     *
     * @return File
     */
    public function transform(FileInput $fileInput, ?File $file): File
    {
        $this->validator->validate($fileInput);

        $targetEntity = $fileInput->targetEntity;
        $type         = $fileInput->type;

        $user         = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            throw new AccessDeniedHttpException();
        }

        if ($targetEntity instanceof Project) {
            if (\in_array($type, ProjectFile::getProjectFileTypes(), true)) {
                $file = $this->uploadForProjectFile($targetEntity, $fileInput, $currentStaff, $file);
            }

            if (\in_array($type, Project::getProjectFileTypes(), true)) {
                $file = $this->uploadForProject($targetEntity, $fileInput, $currentStaff, $file);
            }
        }

        if ($targetEntity instanceof ProjectParticipation) {
            $file = $this->uploadProjectParticipationNda($targetEntity, $fileInput, $currentStaff, $file);
        }

        return $file;
    }

    /**
     * @param Project   $project
     * @param FileInput $fileInput
     * @param Staff     $currentStaff
     * @param File|null $file
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return File
     */
    private function uploadForProjectFile(Project $project, FileInput $fileInput, Staff $currentStaff, ?File $file): File
    {
        if (null === $file) {
            $file        = new File();
            $projectFile = new ProjectFile($fileInput->type, $file, $project, $currentStaff);

            if (false === $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_CREATE, $projectFile)) {
                throw new AccessDeniedException();
            }
        } else {
            $projectFile = $this->projectFileRepository->findOneBy(['file' => $file, 'project' => $project, 'type' => $fileInput->type]);

            if (null === $projectFile) {
                throw new RuntimeException(sprintf(
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

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, null, ['projectId' => $projectFile->getProject()->getId()]);

        $this->projectFileRepository->save($projectFile);

        return $file;
    }

    /**
     * @param Project   $project
     * @param FileInput $fileInput
     * @param Staff     $currentStaff
     * @param File|null $file
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return File
     */
    private function uploadForProject(Project $project, FileInput $fileInput, Staff $currentStaff, ?File $file): File
    {
        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            throw new AccessDeniedException();
        }

        $isPublished = $project->isPublished();

        switch ($fileInput->type) {
            case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                $descriptionDocument = $project->getDescriptionDocument();
                if ($isPublished && null !== $file && null !== $descriptionDocument && $file !== $descriptionDocument) {
                    static::denyUploadExistingFile($fileInput, $descriptionDocument, $project);
                }
                $file = $isPublished && $descriptionDocument ? $descriptionDocument : new File();
                $project->setDescriptionDocument($file);
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
                throw new \InvalidArgumentException(sprintf('You cannot upload the file of the type %s.', $fileInput->type));
        }

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, null, ['projectId' => $project->getId()]);

        $this->projectRepository->save($project);

        return $file;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param FileInput            $fileInput
     * @param Staff                $currentStaff
     * @param File|null            $file
     *
     * @return File
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function uploadProjectParticipationNda(ProjectParticipation $projectParticipation, FileInput $fileInput, Staff $currentStaff, ?File $file)
    {
        if (
            false === $this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation)
            || false === $this->projectParticipationManager->isArranger($projectParticipation, $currentStaff)
        ) {
            throw new AccessDeniedException();
        }

        $isPublished = $projectParticipation->getProject()->isPublished();

        $existingNda = $projectParticipation->getNda();
        if ($isPublished && null !== $file && null !== $existingNda && $file !== $existingNda) {
            static::denyUploadExistingFile($fileInput, $existingNda, $projectParticipation);
        }

        $file = $isPublished && $existingNda ? $existingNda : new File();

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, null, ['projectParticipationId' => $projectParticipation->getId()]);

        $projectParticipation->setNda($file);

        return $file;
    }

    /**
     * @param FileInput $request
     * @param File      $existingFile
     * @param object    $targetEntity
     */
    private static function denyUploadExistingFile(FileInput $request, File $existingFile, object $targetEntity)
    {
        throw new RuntimeException(sprintf(
            'There is already a %s with id %s on the %s %s. You can only update its version',
            $request->type,
            $existingFile->getPublicId(),
            \get_class($targetEntity),
            \method_exists($targetEntity, 'getPublicId') ? $targetEntity->getPublicId() : '',
        ));
    }
}
