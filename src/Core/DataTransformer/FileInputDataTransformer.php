<?php

declare(strict_types=1);

namespace Unilend\Core\DataTransformer;

use ApiPlatform\Core\Validator\ValidatorInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use League\Flysystem\FileExistsException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Security\Voter\TermVoter;
use Unilend\Core\DTO\FileInput;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Message;
use Unilend\Core\Entity\MessageFile;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\MessageFileRepository;
use Unilend\Core\Repository\MessageRepository;
use Unilend\Core\Security\Voter\MessageVoter;
use Unilend\Core\Service\File\FileUploadManager;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectFile;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Repository\ProjectFileRepository;
use Unilend\Syndication\Repository\ProjectRepository;
use Unilend\Syndication\Security\Voter\ProjectFileVoter;
use Unilend\Syndication\Security\Voter\ProjectVoter;

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
     * @throws FileExistsException
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

        if ($targetEntity instanceof Term) {
            $file = $this->uploadTermDocument($targetEntity, $fileInput, $currentStaff);
        }

        return $file;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
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
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function uploadForProjectFile(Project $project, FileInput $fileInput, User $user, ?File $file): File
    {
        $currentStaff = $this->getCurrentStaff();

        if (false === ($currentStaff instanceof Staff)) {
            throw new AccessDeniedException(sprintf('Cannot add new project file if there is no staff attached to logged user'));
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
     * @throws FileExistsException
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

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, ['projectId' => $project->getId()], $this->getCurrentCompany());

        $this->projectRepository->save($project);

        return $file;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return File
     */
    private function uploadProjectParticipationNda(ProjectParticipation $projectParticipation, FileInput $fileInput, User $user, ?File $file)
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
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return File
     */
    private function uploadTermDocument(Term $targetEntity, FileInput $fileInput, User $user)
    {
        if (false === $this->security->isGranted(TermVoter::ATTRIBUTE_EDIT, $targetEntity)) {
            throw new AccessDeniedException();
        }

        $file = new File();

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, ['termId' => $targetEntity->getId()]);

        $targetEntity->setBorrowerDocument($file);

        return $file;
    }

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
