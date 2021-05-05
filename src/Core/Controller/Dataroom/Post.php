<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Dataroom;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Core\Entity\AbstractFolder;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Folder;
use Unilend\Core\Exception\Drive\FolderAlreadyExistsException;

class Post
{
    /**
     * @return Folder
     */
    public function __invoke(Drive $data, Request $request)
    {
        $path         = $request->attributes->get('path');
        $parentFolder = $data->get($path);

        if (false === ($parentFolder instanceof AbstractFolder)) {
            throw new NotFoundHttpException();
        }

        $content = $request->toArray();
        $name    = $content['name'] ?? null;

        try {
            return new Folder($name, $data, $parentFolder->getPath());
        } catch (FolderAlreadyExistsException $e) {
            throw new BadRequestHttpException();
        }
    }
}
