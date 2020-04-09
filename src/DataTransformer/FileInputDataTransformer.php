<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, IOException};
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use League\Flysystem\FileExistsException;
use Prophecy\Exception\InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\{Exception\AccessDeniedException, Security};
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\DTO\FileInput;
use Unilend\Entity\{Clients, File, Project, ProjectFile, Staff};
use Unilend\Repository\{ProjectFileRepository, ProjectRepository};
use Unilend\Security\Voter\{ProjectFileVoter, ProjectVoter};
use Unilend\Service\File\FileUploadManager;

class FileInputDataTransformer
{
    /** @var ValidatorInterface */
    private $validator;
    /** @var IriConverter */
    private $iriConverter;
    /** @var Security */
    private $security;
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var ProjectFileRepository */
    private $projectFileRepository;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @param ValidatorInterface    $validator
     * @param IriConverterInterface $iriConverter
     * @param Security              $security
     * @param FileUploadManager     $fileUploadManager
     * @param ProjectFileRepository $projectFileRepository
     * @param ProjectRepository     $projectRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        IriConverterInterface $iriConverter,
        Security $security,
        FileUploadManager $fileUploadManager,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository
    ) {
        $this->validator             = $validator;
        $this->iriConverter          = $iriConverter;
        $this->security              = $security;
        $this->fileUploadManager     = $fileUploadManager;
        $this->projectFileRepository = $projectFileRepository;
        $this->projectRepository     = $projectRepository;
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

        // No group, no joint, more performance
        $targetEntity = $this->iriConverter->getItemFromIri($fileInput->targetEntity, [AbstractNormalizer::GROUPS => []]);
        $type         = $fileInput->type;

        $user         = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            throw new AccessDeniedHttpException();
        }

        if ($targetEntity instanceof Project) {
            if (in_array($type, ProjectFile::getProjectFileTypes(), true)) {
                $file = $this->uploadForProjectFile($targetEntity, $fileInput, $currentStaff, $file);
            }

            if (in_array($type, Project::getProjectFileTypes(), true)) {
                $file = $this->uploadForProject($targetEntity, $fileInput, $currentStaff, $file);
            }
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

            if (false === $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_EDIT, $projectFile)) {
                throw new AccessDeniedException();
            }
        }
        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, null, ['project' => $projectFile->getProject()]);

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

        switch ($fileInput->type) {
            case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                $descriptionDocument = $project->getDescriptionDocument();
                if (null !== $file && null !== $descriptionDocument && $file !== $descriptionDocument) {
                    throw new RuntimeException(sprintf(
                        'There is already a description file %s on the project %s. You can only update its version',
                        $descriptionDocument->getPublicId(),
                        $project->getHash()
                    ));
                }
                $file = $descriptionDocument ?? new File();
                $project->setDescriptionDocument($file);

                break;
            case Project::PROJECT_FILE_TYPE_CONFIDENTIALITY:
                $confidentialityDisclaimer = $project->getConfidentialityDisclaimer();
                if (null !== $file && null !== $confidentialityDisclaimer && $file !== $confidentialityDisclaimer) {
                    throw new RuntimeException(sprintf(
                        'There is already a confidentiality disclaimer file %s on the project %s. You can only update its version',
                        $confidentialityDisclaimer->getPublicId(),
                        $project->getHash()
                    ));
                }
                $file = $confidentialityDisclaimer ?? new File();
                $project->setConfidentialityDisclaimer($file);

                break;
            default:
                throw new InvalidArgumentException(sprintf('You cannot upload the file of the type %s.', $fileInput->type));
        }

        $this->fileUploadManager->upload($fileInput->uploadedFile, $currentStaff, $file, null, ['project' => $project]);

        $this->projectRepository->save($project);

        return $file;
    }
}
