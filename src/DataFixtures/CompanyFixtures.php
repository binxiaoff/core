<?php

namespace Unilend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\Company;
use Unilend\Entity\CompanyStatus;

class CompanyFixtures extends AbstractFixtures
{

    public const CALS = 'COMPANY_CALS';
    public const COMPANIES = [
        'COMPANY1',
        'COMPANY2',
        'COMPANY3',
        'COMPANY4',
        'COMPANY5',
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        // Main company
        $company = $this->createCompany("CALS Company", "CALS");
        $manager->persist($company);
        $this->addReference(self::CALS, $company);

        // Fake companies
        for ($i = 1; $i <= 5; $i++) {
            $company = $this->createCompany("Company $i");
            $manager->persist($company);
            $this->addReference(self::COMPANIES[$i - 1], $company);
        }

        $manager->flush();
    }

    /**
     * @param string|null $name
     * @param string|null $shortcode
     *
     * @return Company
     *
     * @throws \Exception
     */
    public function createCompany(string $name = null, string $shortcode = null): Company
    {
        $company = (new Company($name ?: $this->faker->company, $name ?: $this->faker->company))
            ->setBankCode($this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($this->faker->vat);
        $status = (new CompanyStatus($company, CompanyStatus::STATUS_SIGNED));
        $company->setCurrentStatus($status);

        return $company;
    }
}
