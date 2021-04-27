<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Participation;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Agency\Entity\Participation;
use Unilend\Core\Entity\Folder;

class Post
{
    /**
     * @return Folder
     */
    public function __invoke(Participation $data, Request $request)
    {
        $drive = $data->getConfidentialDrive();

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
