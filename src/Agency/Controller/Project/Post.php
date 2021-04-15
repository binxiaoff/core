<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Repository\ProjectRepository;
use Unilend\Agency\Security\Voter\ProjectVoter;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Folder;

class Post
{
    private ProjectRepository $projectRepository;
    private Security          $security;

    /**
     * @param ProjectRepository $projectRepository
     * @param Security          $security
     */
    public function __construct(ProjectRepository $projectRepository, Security $security)
    {
        $this->projectRepository = $projectRepository;
        $this->security          = $security;
    }

    /**
     * @param Project $data
     * @param Request $request
     *
     * @return Folder
     */
    public function __invoke(Project $data, Request $request)
    {
        $property = $request->attributes->get('sharedDrive');
        $drive    = null;
        $project  = $this->projectRepository->find($data->getId());

        if ($project && $property) {
            $method = 'get' . ucfirst($property) . 'Drive';

            if (false === method_exists($project, $method)) {
                throw new BadRequestException();
            }

            $drive = $project->{$method}();
        }

        if (false === $drive instanceof Drive) {
            throw new BadRequestException();
        }

        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_CREATE_FOLDER, $project)) {
            throw new BadRequestException();
        }

        $content = $request->toArray();
        $name    = $content['name'] ?? null;

        // throw Exception if name contains other character than letters or spaces
        if (false === ctype_alpha(str_replace(' ', '', $name))) {
            throw new BadRequestException();
        }

        $path         = $request->attributes->get('path');
        $parentFolder = $drive->get($path);

        if (false === $parentFolder instanceof Folder) {
            throw new BadRequestException();
        }

        return new Folder($name, $drive, $parentFolder->getPath());
    }
}
