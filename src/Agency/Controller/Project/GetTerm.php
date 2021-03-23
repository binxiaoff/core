<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Request;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Repository\TermRepository;

class GetTerm
{
    /**
     * @var TermRepository
     */
    private TermRepository $termRepository;

    /**
     * @param TermRepository $termRepository
     */
    public function __construct(TermRepository $termRepository)
    {
        $this->termRepository = $termRepository;
    }

    /**
     * @param Project $data
     * @param Request $request
     *
     * @return iterable|Term[]
     */
    public function __invoke(Project $data, Request $request)
    {
        $archived = $request->get('archived');

        if (true === $archived) {
            $this->termRepository->findArchivedByProject($data);
        }

        if (false === $archived) {
            $this->termRepository->findActiveByProject($data);
        }

        return $this->termRepository->findByProject($data);
    }
}
