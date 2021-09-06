<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyAdmin;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;

trait UserStaffTrait
{
    use PropertyValueTrait;

    /**
     * @throws Exception
     */
    private function createStaff(): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        $user  = new User('user@mail.com');
        $staff = new Staff($user, $team);
        $staff->setPublicId();

        $user->setCurrentStaff($staff);

        return $staff;
    }

    private function createCompanyAdmin(User $user, Company $company): CompanyAdmin
    {
        $companyAdmin = new CompanyAdmin($user, $company);
        $admins       = $company->getAdmins();
        $admins->add($companyAdmin);

        $this->forcePropertyValue($company, 'admins', $admins);

        return $companyAdmin;
    }

    /**
     * @throws Exception
     */
    private function createUserWithStaff(int $staffNb = 1): User
    {
        $staffCollection = new ArrayCollection();
        $staff           = $this->createStaff();
        $user            = $staff->getUser();
        $user->setPublicId();

        foreach (\range(1, $staffNb) as $index) {
            $staffItem = new Staff($user, $staff->getTeam());
            $staffItem->setPublicId();
            $staffCollection->add($staffItem);
        }

        $this->forcePropertyValue($user, 'staff', $staffCollection);

        return $user;
    }
}
