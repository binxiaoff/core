<?php

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionException;
use Unilend\Entity\Clients;
use Unilend\Entity\Company;
use Unilend\Entity\CompanyModule;
use Unilend\Entity\CompanyStatus;

class CompanyFixtures extends AbstractFixtures implements DependentFixtureInterface
{

    public const CALS = 'COMPANY_CALS';
    public const COMPANY1 = 'COMPANY1';
    public const COMPANY2 = 'COMPANY2';
    public const COMPANY3 = 'COMPANY3';
    public const COMPANY4 = 'COMPANY4';
    public const COMPANY5 = 'COMPANY5';
    public const COMPANY_NOT_SIGNED = 'COMPANY_NOT_SIGNED';
    public const COMPANY_EXTERNAL = 'COMPANY_EXTERNAL';
    public const COMPANIES = [
        self::COMPANY1,
        self::COMPANY2,
        self::COMPANY3,
        self::COMPANY4,
        self::COMPANY5,
        self::COMPANY_EXTERNAL,
        self::COMPANY_NOT_SIGNED,
    ];

    /**
     * @param ObjectManager $manager
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        // Main company
        /** @var Clients $user */
        $user = $this->getReference(UserFixtures::ADMIN);
        $domain = explode('@', $user->getEmail())[1];
        $company = $this->createCompany("CALS Company", "CALS")->setEmailDomain($domain);
        $manager->persist($company);
        $this->addReference(self::CALS, $company);

        // Fake bank
        for ($i = 1; $i <= 5; $i++) {
            $company = $this->createCompany("CA Bank $i")->setGroupName('Crédit Agricole');
            $manager->persist($company);
            $this->addReference(self::COMPANIES[$i - 1], $company);
            $this->createCompanyModule($manager, $company);
        }

        for ($i = 6; $i <= 50; $i++) {
            $company = $this->createCompany("CA Bank $i")->setGroupName('Crédit Agricole');
            $manager->persist($company);
            $this->createCompanyModule($manager, $company);
        }

        // External bank
        $company = $this->createCompany("External Bank");
        $manager->persist($company);
        $this->addReference(self::COMPANY_EXTERNAL, $company);
        $this->createCompanyModule($manager, $company);

        $company = $this->createCompany('Not signed Bank', 'C', CompanyStatus::STATUS_PROSPECT)->setGroupName('Crédit Agricole');
        $manager->persist($company);
        $this->addReference(self::COMPANY_NOT_SIGNED, $company);
        $this->createCompanyModule($manager, $company);

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    /**
     * @param string|null $name
     * @param string|null $shortcode
     * @param int         $status
     *
     * @return Company
     *
     * @throws ReflectionException
     * @throws Exception
     */
    private function createCompany(string $name = null, string $shortcode = null, int $status = CompanyStatus::STATUS_SIGNED): Company
    {
        $companyName = $name ?: $this->faker->company;
        $company = (new Company($companyName, $companyName))
            ->setBankCode($this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($this->faker->vat);
        $this->forcePublicId($company, Urlizer::urlize($companyName));
        $companyStatus = new CompanyStatus($company, $status);
        $company->setCurrentStatus($companyStatus);

        return $company;
    }

    /**
     * @param ObjectManager $manager
     * @param Company       $company
     * @param array         $moduleConfigurations
     *
     * @throws Exception
     */
    private function createCompanyModule(
        ObjectManager $manager,
        Company $company,
        array $moduleConfigurations = [CompanyModule::MODULE_ARRANGEMENT => false, CompanyModule::MODULE_PARTICIPATION => false, CompanyModule::MODULE_AGENCY => false]
    ): void {
        foreach ($moduleConfigurations as $module => $value) {
            $companyModule = new CompanyModule($module, $company, $value);
            $manager->persist($companyModule);
        }
    }
}
