<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

use Closure;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker;
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
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    /**
     * {@inheritDoc}
     *
     * @throws ReflectionException
     * @throws Exception
     */
    final public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
        // Works because Faker is set to Fr_fr
        $company = new Company($this->getName(), $this->getName(), $faker->siren(false));
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

    abstract protected function getName(): string;

    /**
     * @return mixed
     */
    abstract protected function getTeams(Team $companyRootTeam);

    protected function getShortCode(): string
    {
        return static::getName();
    }

    protected function getBankCode(): string
    {
        return static::getName();
    }

    /**
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
     * @throws Exception
     *
     * @return Staff
     */
    protected function createManager(User $user, Team $team)
    {
        $staff = $this->createStaff($user, $team);
        $staff->setManager(true);

        return $staff;
    }

    /**
     * @throws Exception
     *
     * @return Staff
     */
    protected function createStaff(User $user, Team $team)
    {
        return new Staff($user, $team);
    }

    final protected function getTeamFactory(Team $parent): Closure
    {
        return static function ($name) use ($parent) {
            return Team::createTeam((string) $name, $parent);
        };
    }

    protected function getCompanyGroup(): ?CompanyGroup
    {
        return null;
    }
}
