<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Folder;

class Post
{
    /**
     * @return Folder
     */
    public function __invoke(Project $data, Request $request)
    {
        $property = $request->attributes->get('sharedDrive');
        $drive    = null;

        if ($property) {
            $method = 'get' . ucfirst($property) . 'Drive';

            if (false === method_exists($data, $method)) {
                throw new BadRequestException();
            }

            $drive = $data->{$method}();
        }

        if (false === $drive instanceof Drive) {
            throw new BadRequestException();
        }

        // TODO add voter

        $content      = $request->toArray();
        $name         = $content['name'] ?? null;
        $path         = $request->attributes->get('path');
        $parentFolder = $drive->get($path);

        if (false === $parentFolder instanceof Folder) {
            throw new BadRequestException();
        }

        return new Folder($name, $drive, $parentFolder->getPath());
    }
}
