<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\{Staff};
use Unilend\Core\Repository\CompanyAdminRepository;
use Unilend\Core\Repository\StaffRepository;

class StaffVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW       = 'view';
    public const ATTRIBUTE_EDIT       = 'edit';
    public const ATTRIBUTE_DELETE     = 'delete';
    public const ATTRIBUTE_CREATE     = 'create';

    /** @var CompanyAdminRepository */
    private CompanyAdminRepository $companyAdminRepository;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param CompanyAdminRepository        $companyAdminRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        CompanyAdminRepository $companyAdminRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->companyAdminRepository = $companyAdminRepository;
    }

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        $currentStaff = $user->getCurrentStaff();

        if (false === ($currentStaff instanceof Staff)) {
            return false;
        }

        return false === $currentStaff->isArchived() && false === $subject->isArchived();
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

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function canCreate(Staff $subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (false === $submitterStaff instanceof Staff) {
            return false;
        }

        return
            (
                // You can create a staff for external banks
                false === $subject->getCompany()->isCAGMember()
                || (
                    $submitterStaff->getCompany() === $subject->getCompany()
                    && $submitterStaff->isManager()
                )
            )
            // You must be connected with a crédit agricole group bank
            && $submitterStaff->getCompany()->isCAGMember();
    }

    /**
     * @param $subject
     * @param User $user
     *
     * @return bool
     */
    protected function canView($subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        return $subject === $submitterStaff || $this->isSuperior($submitterStaff, $subject);
    }

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function canEdit(Staff $subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $submitterStaff->isManager()) {
            return false;
        }

        // A staff cannot edit self
        if ($submitterStaff->getPublicId() === $subject->getPublicId()) {
            return false;
        }

        return $this->isSuperior($submitterStaff, $subject);
    }

    /**
     * @param $superior
     * @param $subordinate
     *
     * @return bool
     */
    private function isSuperior($superior, $subordinate)
    {
        if (false === $superior->isManager()) {
            return false;
        }

        return \in_array($superior->getTeam(), $subordinate->getTeam()->getAncestors(), true) || $superior->getTeam() === $subordinate->getTeam();
    }
}
