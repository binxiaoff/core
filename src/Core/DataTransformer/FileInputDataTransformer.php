<?php

declare(strict_types=1);

namespace KLS\Core\DataTransformer;

use ApiPlatform\Core\Validator\ValidatorInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageFile;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Repository\MessageFileRepository;
use KLS\Core\Repository\MessageRepository;
use KLS\Core\Security\Voter\MessageVoter;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Security\Voter\TermVoter;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Repository\ProjectFileRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Security\Voter\ProjectFileVoter;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use League\Flysystem\FilesystemException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class FileInputDataTransformer
{
    private ValidatorInterface $validator;
    private Security $security;
    private FileUploadManager $fileUploadManager;
    private ProjectFileRepository $projectFileRepository;
    private ProjectRepository $projectRepository;
    private MessageFileRepository $messageFileRepository;
    private MessageRepository $messageRepository;

    public function __construct(
        ValidatorInterface $validator,
        Security $security,
        FileUploadManager $fileUploadManager,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository,
        MessageFileRepository $messageFileRepository,
        MessageRepository $messageRepository
    ) {
        $this->validator             = $validator;
        $this->security              = $security;
        $this->fileUploadManager     = $fileUploadManager;
        $this->projectFileRepository = $projectFileRepository;
        $this->projectRepository     = $projectRepository;
        $this->messageFileRepository = $messageFileRepository;
        $this->messageRepository     = $messageRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FilesystemException
     * @throws Exception
     */
    public function transform(FileInput $fileInput, ?File $file): File
    {
        $this->validator->validate($fileInput);

        $targetEntity = $fileInput->targetEntity;
        $type         = $fileInput->type;

        $user = $this->security->getUser();

        if (false === $user instanceof User) {
            throw new AccessDeniedHttpException('Attempt to transform fileInput into file without valid user');
        }

        if ($targetEntity instanceof Project) {
            if (\in_array($type, ProjectFile::getProjectFileTypes(), true)) {
                $file = $this->uploadForProjectFile($targetEntity, $fileInput, $user, $file);
            }

            if (\in_array($type, Project::getProjectFileTypes(), true)) {
                $file = $this->uploadForProject($targetEntity, $fileInput, $user, $file);
            }
        }

        if ($targetEntity instanceof ProjectParticipation) {
            $file = $this->uploadProjectParticipationNda($targetEntity, $fileInput, $user, $file);
        }

        if ($targetEntity instanceof Message) {
            $file = $this->uploadMessageFile($targetEntity, $fileInput, $user, $file);
        }

        if ($targetEntity instanceof Term) {
            $file = $this->uploadTermDocument($targetEntity, $fileInput, $user);
        }

        return $file;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function uploadMessageFile(Message $message, FileInput $fileInput, User $user, ?File $file): File
    {
        if (false === $this->security->isGranted(MessageVoter::ATTRIBUTE_ATTACH_FILE, $message)) {
            throw new AccessDeniedException();
        }

        if (false === $file instanceof File) {
            $file = new File();
        }

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, [], $this->getCurrentCompany());

        $messagesToBeAttached = [$message];

        // If it's a broadcast message, then add messageFile to all broadcast messages
        if ($message->isBroadcast()) {
            $messagesToBeAttached = $this->messageRepository->findBy(['broadcast' => $message->getBroadcast()]);
        }

        foreach ($messagesToBeAttached as $messageToAddMessageFile) {
            $messageFile = new MessageFile($file, $messageToAddMessageFile);
            $this->messageFileRepository->persist($messageFile);
        }

        $this->messageFileRepository->flush();

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

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function uploadProjectParticipationNda(ProjectParticipation $projectParticipation, FileInput $fileInput, User $user, ?File $file): File
    {
        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectParticipation->getProject())) {
            throw new AccessDeniedException();
        }

        $isPublished = $projectParticipation->getProject()->isPublished();

        $existingNda = $projectParticipation->getNda();
        if ($isPublished && null !== $file && null !== $existingNda && $file !== $existingNda) {
            static::denyUploadExistingFile($fileInput, $existingNda, $projectParticipation);
        }

        $file = $isPublished && $existingNda ? $existingNda : new File();

        $this->fileUploadManager->upload(
            $fileInput->uploadedFile,
            $user,
            $file,
            ['projectParticipationId' => $projectParticipation->getId()],
            $this->getCurrentCompany()
        );

        $projectParticipation->setNda($file);

        return $file;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function uploadTermDocument(Term $targetEntity, FileInput $fileInput, User $user): File
    {
        if (false === $this->security->isGranted(TermVoter::ATTRIBUTE_EDIT, $targetEntity)) {
            throw new AccessDeniedException();
        }

        $file = new File();

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, ['termId' => $targetEntity->getId()]);

        $targetEntity->setBorrowerDocument($file);

        return $file;
    }

    private static function denyUploadExistingFile(FileInput $request, File $existingFile, object $targetEntity): void
    {
        throw new RuntimeException(\sprintf(
            'There is already a %s with id %s on the %s %s. You can only update its version',
            $request->type,
            $existingFile->getPublicId(),
            \get_class($targetEntity),
            \method_exists($targetEntity, 'getPublicId') ? $targetEntity->getPublicId() : '',
        ));
    }

    private function getCurrentStaff(): ?Staff
    {
        $token = $this->security->getToken();

        return $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;
    }

    private function getCurrentCompany(): ?Company
    {
        $token = $this->security->getToken();

        return $token && $token->hasAttribute('company') ? $token->getAttribute('company') : null;
    }
}
