<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Participation;

use _HumbugBox373c0874430e\Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Security\Voter\ParticipationVoter;
use Unilend\Core\Entity\Folder;

class Get
{
    /** @var Security  */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param Participation $data
     * @param Request       $request
     *
     * @return Folder
     */
    public function __invoke(Participation $data, Request $request)
    {
        if (false === $this->security->isGranted(ParticipationVoter::ATTRIBUTE_VIEW_DRIVE, $data)) {
            throw new AccessDeniedException();
        }

        $drive  = $data->getPersonalDrive();
        $return = $drive->get($request->get('path', '/') ?? '/');

        if ($return) {
            return $return;
        }

        throw new NotFoundHttpException();
    }
}
