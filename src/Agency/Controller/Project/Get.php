<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Drive;

class Get
{
    /** @var Security  */
    private Security $security;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /**
     * @param Security              $security
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(Security $security, IriConverterInterface $iriConverter)
    {
        $this->security = $security;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @param Project $data
     * @param Request $request
     *
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

        $return = $drive->get($request->get('path', '/') ?? '/');

        if ($return) {
            return $return;
        }

        throw new NotFoundHttpException();
    }
}
