<?php

declare(strict_types=1);

namespace KLS\Agency\Controller\Project;

use KLS\Agency\Entity\Covenant;
use KLS\Agency\Entity\Project;
use KLS\Agency\Entity\Term;
use KLS\Agency\Security\Voter\ProjectRoleVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
