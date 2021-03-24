<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\CompanyAdminRepository;

class CompanyModuleVoter extends AbstractEntityVoter
{
    /** @var string */
    public const ATTRIBUTE_EDIT = 'edit';

    /** @var CompanyAdminRepository */
    private CompanyAdminRepository $companyAdminRepository;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param CompanyAdminRepository        $companyAdminRepository
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, CompanyAdminRepository $companyAdminRepository)
    {
        parent::__construct($authorizationChecker);
        $this->companyAdminRepository = $companyAdminRepository;
    }

    /**
     * @param Staff $subject
     * @param User  $submitterUser
     *
     * @return bool
     */
    protected function isGrantedAll($subject, User $submitterUser): bool
    {
        $company = $subject->getCompany();

        return null !== $this->companyAdminRepository->findOneBy(['company' => $company, 'user' => $submitterUser]);
    }
}
