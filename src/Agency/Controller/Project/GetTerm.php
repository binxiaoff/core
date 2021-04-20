<?php

declare(strict_types=1);

namespace Unilend\Agency\Controller\Project;

use Symfony\Component\HttpFoundation\Request;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Repository\TermRepository;

class GetTerm
{
    private TermRepository $termRepository;

    public function __construct(TermRepository $termRepository)
    {
        $this->termRepository = $termRepository;
    }

    /**
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
