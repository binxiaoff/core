<?php

namespace Unilend\Core\Controller\Company;

use Unilend\Core\Entity\Company;
use Unilend\Core\Repository\TeamRepository;

class Team
{
    /** @var TeamRepository */
    private TeamRepository $teamRepository;

    /**
     * @param TeamRepository $teamRepository
     */
    public function __construct(TeamRepository $teamRepository)
    {
        $this->teamRepository = $teamRepository;
    }

    /**
     * @param Company $data
     *
     * @return iterable
     */
    public function __invoke(Company $data)
    {
        return $this->teamRepository->findByCompany($data);
    }
}
