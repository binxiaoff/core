<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Security\Voter\ProjectRoleVoter;

class GetCovenants
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return iterable|Term[]
     */
    public function __invoke(Project $data, Request $request)
    {
        $isAgent = $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $data);

        return $data->getCovenants()->filter(function (Covenant $covenant) use ($isAgent) {
            return $covenant->isPublished() || $isAgent;
        });
    }
}
