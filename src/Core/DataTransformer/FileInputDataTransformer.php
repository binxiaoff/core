<?php

declare(strict_types=1);

namespace Unilend\Core\DataTransformer;

use ApiPlatform\Core\Validator\ValidatorInterface;
use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, IOException};
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use League\Flysystem\FileExistsException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\{Exception\AccessDeniedException, Security};
use Unilend\Core\DTO\FileInput;
use Unilend\Core\Entity\{File, Message, MessageFile, Staff, User};
use Unilend\Core\Repository\{MessageFileRepository, MessageRepository, MessageThreadRepository};
use Unilend\Core\Security\Voter\MessageVoter;
use Unilend\Core\Service\File\FileUploadManager;
use Unilend\Syndication\Entity\{Project,
    ProjectFile,
    ProjectParticipation
};
use Unilend\Syndication\Repository\{ProjectFileRepository, ProjectRepository};
use Unilend\Syndication\Security\Voter\{ProjectFileVoter, ProjectParticipationVoter, ProjectVoter};
use Unilend\Syndication\Service\Project\ProjectManager;

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
    /** @var MessageFileRepository */
    private MessageFileRepository $messageFileRepository;
    /** @var ProjectManager */
    private ProjectManager $projectManager;
    /** @var MessageThreadRepository */
    private MessageThreadRepository $messageThreadRepository;
    /** @var MessageRepository */
    private MessageRepository $messageRepository;

    /**
     * FileInputDataTransformer constructor.
     *
     * @param ValidatorInterface      $validator
     * @param Security                $security
     * @param FileUploadManager       $fileUploadManager
     * @param ProjectManager          $projectManager
     * @param ProjectFileRepository   $projectFileRepository
     * @param ProjectRepository       $projectRepository
     * @param MessageFileRepository   $messageFileRepository
     * @param MessageThreadRepository $messageThreadRepository
     * @param MessageRepository       $messageRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        Security $security,
        FileUploadManager $fileUploadManager,
        ProjectManager $projectManager,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository,
        MessageFileRepository $messageFileRepository,
        MessageThreadRepository $messageThreadRepository,
        MessageRepository $messageRepository
    ) {
        $this->validator                = $validator;
        $this->security                 = $security;
        $this->fileUploadManager        = $fileUploadManager;
        $this->projectFileRepository    = $projectFileRepository;
        $this->projectRepository        = $projectRepository;
        $this->messageFileRepository    = $messageFileRepository;
        $this->projectManager           = $projectManager;
        $this->messageThreadRepository  = $messageThreadRepository;
        $this->messageRepository        = $messageRepository;
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
        $currentStaff = $user instanceof User ? $user->getCurrentStaff() : null;

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

        if ($targetEntity instanceof Message) {
            $file = $this->uploadMessageFile($targetEntity, $fileInput, $currentStaff, $file);
        }

        return $file;
    }

    /**
     * @param Message   $message
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
    private function uploadMessageFile(Message $message, FileInput $fileInput, Staff $currentStaff, ?File $file): File
    {
        if (false === $this->security->isGranted(MessageVoter::ATTRIBUTE_ATTACH_FILE, $message)) {
            throw new AccessDeniedException();
        }

        if (false === $file instanceof File) {
            $file        = new File();
        }
        $messageFile = new MessageFile($file, $message);

        // If it's a broadcast message, then add messageFile to all broadcast messages
        if ($message->isBroadcast()) {
            $messages = $this->messageRepository->findBy(['broadcast' => $message->getBroadcast()]);
            foreach ($messages as $messageToAddMessageFile) {
                if ($messageToAddMessageFile !== $message) {
                    $messageFileBroadcast = new MessageFile($file, $messageToAddMessageFile);
                    $this->messageFileRepository->save($messageFileBroadcast);
                }
            }
        }
        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, ['messageId' => $messageFile->getMessage()->getId()]);
        $this->messageFileRepository->save($messageFile);

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

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, ['projectId' => $projectFile->getProject()->getId()]);

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

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, ['projectId' => $project->getId()]);

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
            || false === $this->projectManager->isArranger($projectParticipation->getProject(), $currentStaff)
        ) {
            throw new AccessDeniedException();
        }

        $isPublished = $projectParticipation->getProject()->isPublished();

        $existingNda = $projectParticipation->getNda();
        if ($isPublished && null !== $file && null !== $existingNda && $file !== $existingNda) {
            static::denyUploadExistingFile($fileInput, $existingNda, $projectParticipation);
        }

        $file = $isPublished && $existingNda ? $existingNda : new File();

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, ['projectParticipationId' => $projectParticipation->getId()]);

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
