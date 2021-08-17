<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\File;
use KLS\Core\Service\File\FileDeleteInterface;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Repository\ProjectFileRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Security\Voter\ProjectFileVoter;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class FileDeleteManager implements FileDeleteInterface
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

    public function supports(string $type): bool
    {
        return $this->isProjectFileType($type) || $this->isProjectType($type);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(File $file, string $type): void
    {
        if ($this->isProjectFileType($type)) {
            $this->deleteForProjectFile($file, $type);

            return;
        }

        if ($this->isProjectType($type)) {
            $this->deleteForProject($file, $type);

            return;
        }

        $this->throwException($file, $type);
    }

    private function isProjectFileType(string $type): bool
    {
        return \in_array($type, ProjectFile::getProjectFileTypes(), true);
    }

    private function isProjectType(string $type): bool
    {
        return \in_array($type, Project::getProjectFileTypes(), true);
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
