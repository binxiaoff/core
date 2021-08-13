<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Controller\Project;

use Doctrine\Common\Collections\ArrayCollection;
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
        $archived = $request->get('archived');

        $isBorrower = $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $data);
        $isAgent    = $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $data);

        $terms = $this->termRepository->findByProject($data);

        $terms = new ArrayCollection($terms);

        return $terms->filter(static function (Term $term) use ($archived, $isBorrower, $isAgent) {
            return ($term->isArchived() === $archived || null === $archived)
                && ($term->getCovenant()->isPublished() || $isAgent)
                && ($term->isShared() || $isAgent || $isBorrower);
        });
    }
}
