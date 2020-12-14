<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Company;

use Unilend\Core\Entity\Company;
use Unilend\Core\Repository\StaffRepository;

class Staff
{
    /**
     * @var StaffRepository
     */
    private StaffRepository $staffRepository;

    /**
     * @param StaffRepository $staff
     */
    public function __construct(StaffRepository $staff)
    {
        $this->staffRepository = $staff;
    }

    /**
     * @param Company $data
     *
     * @return iterable
     */
    public function __invoke(Company $data)
    {
        return $this->staffRepository->findByCompany($data);
    }
}
