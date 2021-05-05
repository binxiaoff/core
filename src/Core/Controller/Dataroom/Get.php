<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Dataroom;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Folder;

class Get
{
    /**
     * @return Drive|Folder|File
     */
    public function __invoke(Drive $data, Request $request)
    {
        $return = $data->get($request->get('path'));

        if ($return) {
            return $return;
        }

        throw new NotFoundHttpException();
    }
}
