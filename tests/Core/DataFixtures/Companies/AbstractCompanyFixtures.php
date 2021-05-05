<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

use Closure;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use ReflectionException;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyAdmin;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;
use Unilend\Test\Core\DataFixtures\UserFixtures;

abstract class AbstractCompanyFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     * @throws Exception
     */
    final public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
        $company = new Company($this->getName(), $this->getName());
        $manager->persist($company->getRootTeam());
        $company->setShortCode($this->getShortCode());
        $company->setBankCode($this->getBankCode());
        $company->setCompanyGroup($this->getCompanyGroup());
        $companyReference = 'company:' . $this->getName();

        $teams = [...array_values($this->getTeams($company->getRootTeam())), $company->getRootTeam()];
        foreach ($teams as $team) {
            $this->setPublicId($team, 'team:' . $team->getName() . '_' . $companyReference);
            $manager->persist($team);

            foreach ($this->getStaff($team) as $staff) {
                $staffReference = 'staff_' . $companyReference . '_' . $staff->getUser()->getPublicId();
                $this->setPublicId($staff, $staffReference);
                $this->addReference($staffReference, $staff);

                $manager->persist($staff);
            }
        }

        unset($teams);

        foreach ($this->getAdmins($company) as $admin) {
            $manager->persist($admin);
        }

        $this->setPublicId($company, $companyReference);
        $this->addReference($companyReference, $company);

        $manager->persist($company);
        $manager->flush();
        $manager->clear();
    }

    /**
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * @param Team $companyRootTeam
     *
     * @return mixed
     */
    abstract protected function getTeams(Team $companyRootTeam);

    /**
     * @return string
     */
    protected function getShortCode(): string
    {
        return static::getName();
    }

    /**
     * @return string
     */
    protected function getBankCode(): string
    {
        return static::getName();
    }

    /**
     * @param Company $company
     *
     * @return array|CompanyAdmin[]
     */
    abstract protected function getAdmins(Company $company): array;

    /**
     * @param $team
     *
     * @return array|Staff[]
     */
    abstract protected function getStaff(Team $team): array;

    /**
     * @param User $user
     * @param Team $team
     *
     * @return Staff
     *
     * @throws Exception
     */
    protected function createManager(User $user, Team $team)
    {
        $staff = $this->createStaff($user, $team);
        $staff->setManager(true);

        return $staff;
    }

    /**
     * @param User $user
     * @param Team $team
     *
     * @return Staff
     *
     * @throws Exception
     */
    protected function createStaff(User $user, Team $team)
    {
        return new Staff($user, $team);
    }

    /**
     * @param Team $parent
     *
     * @return Closure
     */
    final protected function getTeamFactory(Team $parent): Closure
    {
        return static function ($name) use ($parent) {
            return Team::createTeam((string) $name, $parent);
        };
    }

    /**
     * @return CompanyGroup|null
     */
    protected function getCompanyGroup(): ?CompanyGroup
    {
        return null;
    }
}
