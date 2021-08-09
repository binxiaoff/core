<?php

declare(strict_types=1);

namespace Unilend\Core\Service\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\File;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectFile;
use Unilend\Syndication\Repository\ProjectFileRepository;
use Unilend\Syndication\Repository\ProjectRepository;
use Unilend\Syndication\Security\Voter\ProjectFileVoter;
use Unilend\Syndication\Security\Voter\ProjectVoter;

class FileDeleteManager
{
    private Security $security;
    private ProjectRepository $projectRepository;
    private ProjectFileRepository $projectFileRepository;

    public function __construct(Security $security, ProjectRepository $projectRepository, ProjectFileRepository $projectFileRepository)
    {
        $this->security              = $security;
        $this->projectRepository     = $projectRepository;
        $this->projectFileRepository = $projectFileRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(File $file, string $type): void
    {
        if (\in_array($type, ProjectFile::getProjectFileTypes(), true)) {
            $this->deleteForProjectFile($file, $type);

            return;
        }

        if (\in_array($type, Project::getProjectFileTypes(), true)) {
            $this->deleteForProject($file, $type);

            return;
        }

        $this->throwException($file, $type);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function deleteForProject(File $file, string $type): void
    {
        $field = null;

        switch ($type) {
            case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                $field = 'termSheet';

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

        $setter = 'set' . \ucfirst($field);
        $project->{$setter}(null);

        $this->projectRepository->flush();
    }

    /**
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

    private function throwException(File $file, string $type): void
    {
        throw new NotFoundHttpException(\sprintf('Unable to delete the file "%s" of type "%s"', $file->getPublicId(), $type));
    }
}
