<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Participation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Agency\Entity\Participation;
use Unilend\Core\Entity\Folder;

class Get
{
    /**
     * @param Participation $data
     * @param Request       $request
     *
     * @return Folder
     */
    public function __invoke(Participation $data, Request $request)
    {
        $drive  = $data->getConfidentialDrive();
        $return = $drive->get($request->get('path', '/') ?? '/');

        if ($return) {
            return $return;
        }

        throw new NotFoundHttpException();
    }
}
