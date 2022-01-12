<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Controller\Project;

use KLS\Syndication\Agency\Entity\Covenant;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Security\Voter\ProjectRoleVoter;
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
    public function __invoke(Project $data): iterable
    {
        $isAgent = $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $data);

        return $data->getCovenants()->filter(function (Covenant $covenant) use ($isAgent) {
            return $covenant->isPublished() || $isAgent;
        });
    }
}
