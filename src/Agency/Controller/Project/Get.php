<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Drive;

class Get
{
    /**
     * @return Project
     */
    public function __invoke(Project $data, Request $request)
    {
        $property = $request->get('sharedDrive');

        $drive = null;

        if ($property) {
            $method = 'get' . ucfirst($property) . 'Drive';

            if (false === method_exists($data, $method)) {
                throw new NotFoundHttpException();
            }

            $drive = $data->{$method}();
        }

        if (false === $drive instanceof Drive) {
            throw new NotFoundHttpException();
        }

        $return = $drive->get($request->get('path'));

        if ($return) {
            return $return;
        }

        throw new NotFoundHttpException();
    }
}
