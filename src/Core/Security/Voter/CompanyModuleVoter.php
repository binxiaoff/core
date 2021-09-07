<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Repository\CompanyAdminRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
