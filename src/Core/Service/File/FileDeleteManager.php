<?php

declare(strict_types=1);

namespace Unilend\Core\Service\File;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\{Exception\AccessDeniedException, Security};
use Unilend\Core\Entity\File;
use Unilend\Security\Voter\{ProjectFileVoter, ProjectVoter};
use Unilend\Syndication\Entity\{Project, ProjectFile};
use Unilend\Syndication\Repository\{ProjectFileRepository, ProjectRepository};

class FileDeleteManager
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectFileRepository */
    private $projectFileRepository;
    /** @var Security */
    private $security;

    /**
     * @param ProjectRepository     $projectRepository
     * @param ProjectFileRepository $projectFileRepository
     * @param Security              $security
     */
    public function __construct(ProjectRepository $projectRepository, ProjectFileRepository $projectFileRepository, Security $security)
    {
        $this->projectRepository     = $projectRepository;
        $this->projectFileRepository = $projectFileRepository;
        $this->security              = $security;
    }

    /**
     * @param File   $file
     * @param string $type
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(File $file, string $type): void
    {
        if (in_array($type, ProjectFile::getProjectFileTypes(), true)) {
            $this->deleteForProjectFile($file, $type);

            return;
        }

        if (in_array($type, Project::getProjectFileTypes(), true)) {
            $this->deleteForProject($file, $type);

            return;
        }

        $this->throwException($file, $type);
    }

    /**
     * @param File   $file
     * @param string $type
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function deleteForProject(File $file, string $type): void
    {
        $field = null;
        switch ($type) {
            case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                $field = 'descriptionDocument';

                break;
            case Project::PROJECT_FILE_TYPE_NDA:
                $field = 'nda';

                break;
            default:
                $this->throwException($file, $type);
        }

        $project = $this->projectRepository->findOneBy([$field => $file]);

        if (null === $project) {
            $this->throwException($file, $type);
        }

        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            throw new AccessDeniedException();
        }

        $setter = 'set' . ucfirst($field);
        $project->{$setter}(null);

        $this->projectRepository->flush();
    }

    /**
     * @param File   $file
     * @param string $type
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function deleteForProjectFile(File $file, string $type): void
    {
        $projectFile = $this->projectFileRepository->findOneBy(['file' => $file]);

        if (null === $projectFile) {
            $this->throwException($file, $type);
        }

        if (false === $this->security->isGranted(ProjectFileVoter::ATTRIBUTE_DELETE, $projectFile)) {
            throw new AccessDeniedException();
        }

        $this->projectFileRepository->remove($projectFile);
    }

    /**
     * @param File   $file
     * @param string $type
     */
    private function throwException(File $file, string $type): void
    {
        throw new NotFoundHttpException(sprintf('Unable to delete the file "%s" of type "%s"', $file->getPublicId(), $type));
    }
}
