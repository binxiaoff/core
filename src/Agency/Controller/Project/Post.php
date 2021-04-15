<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Repository\ProjectRepository;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Folder;

class Post
{
    private ProjectRepository $projectRepository;

    /**
     * @param ProjectRepository $projectRepository
     */
    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
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

        if ($property) {
            $project = $this->projectRepository->find($data->getId());
            $method  = 'get' . ucfirst($property) . 'Drive';

            if (null === $project || false === method_exists($project, $method)) {
                throw new BadRequestException('Method not exists');
            }

            $drive = $project->{$method}();
        }

        if (false === $drive instanceof Drive) {
            throw new BadRequestException('No drive');
        }

        $content = $request->toArray();
        $name    = $content['name'] ?? null;

        // throw Exception if name contains other character than letters or spaces
        if (false === ctype_alpha(str_replace(' ', '', $name))) {
            throw new BadRequestException('Name is invalid');
        }

        $path         = $request->attributes->get('path');
        $parentFolder = $drive->get($path);

        if (false === $parentFolder instanceof Folder) {
            throw new BadRequestException('Parent folder does not exists');
        }

        return new Folder($name, $drive, $parentFolder->getPath());
    }
}
