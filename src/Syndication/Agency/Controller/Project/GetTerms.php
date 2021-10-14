<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Controller\Project;

use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Repository\TermRepository;
use KLS\Syndication\Agency\Security\Voter\ProjectRoleVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class GetTerms
{
    private TermRepository $termRepository;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TermRepository $termRepository)
    {
        $this->termRepository       = $termRepository;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return iterable|Term[]
     */
    public function __invoke(Project $data, Request $request)
    {
        $isBorrower = $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $data);
        $isAgent    = $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $data);

        return ($isAgent || $isBorrower) ? $this->termRepository->findByProject($data) : $this->termRepository->findSharedByProject($data);
    }
}
