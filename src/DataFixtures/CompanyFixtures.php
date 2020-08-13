<?php

namespace Unilend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\Clients;
use Unilend\Entity\Company;
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
        }

        // External bank
        $company = $this->createCompany("External Bank");
        $manager->persist($company);
        $this->addReference(self::COMPANY_EXTERNAL, $company);

        $company = $this->createCompany('Not signed Bank', 'C', CompanyStatus::STATUS_PROSPECT)->setGroupName('Crédit Agricole');
        $manager->persist($company);
        $this->addReference(self::COMPANY_NOT_SIGNED, $company);

        $manager->flush();
    }

    /**
     * @param string|null $name
     * @param string|null $shortcode
     * @param int         $status
     *
     * @return Company
     *
     * @throws \Exception
     */
    public function createCompany(string $name = null, string $shortcode = null, string $status = CompanyStatus::STATUS_SIGNED): Company
    {
        $company = (new Company($name ?: $this->faker->company, $name ?: $this->faker->company))
            ->setBankCode($this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($this->faker->vat);
        $status = (new CompanyStatus($company, $status));
        $company->setCurrentStatus($status);

        return $company;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
