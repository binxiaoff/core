<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\CompanyAdminRepository;

class CompanyModuleVoter extends AbstractEntityVoter
{
    private CompanyAdminRepository $companyAdminRepository;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, CompanyAdminRepository $companyAdminRepository)
    {
        parent::__construct($authorizationChecker);
        $this->companyAdminRepository = $companyAdminRepository;
    }

    /**
     * @param Staff $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $company = $subject->getCompany();
        $staff   = $user->getCurrentStaff();

        return $staff instanceof Staff
            && $staff->getCompany() === $company
            && null !== $this->companyAdminRepository->findOneBy(['company' => $company, 'user' => $user]);
    }
}
