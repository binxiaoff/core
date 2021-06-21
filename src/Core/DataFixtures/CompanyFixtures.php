<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionException;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyStatus;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;

class CompanyFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const REFERENCE_PREFIX = 'company:';
    public const KLS              = self::REFERENCE_PREFIX . Company::SHORT_CODE_KLS;
    public const CASA             = self::REFERENCE_PREFIX . Company::SHORT_CODE_CASA;

    public const COMPANY_MANY_STAFF = 'COMPANY_MANY_STAFF';
    public const COMPANY_EXTERNAL   = 'COMPANY_EXTERNAL';

    public const COMPANIES = [
        self::REFERENCE_PREFIX . self::CA_SHORTCODE[0],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE[1],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE[2],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE[3],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE[4],
        self::COMPANY_EXTERNAL,
        self::COMPANY_NOT_SIGNED,
    ];
    private const COMPANY_NOT_SIGNED            = 'COMPANY_NOT_SIGNED';
    private const COMPANY_NOT_SIGNED_NO_MEMBERS = 'COMPANY_NOT_SIGNED_NO_MEMBERS';

    private const CA_SHORTCODE = [
        'ALVO', 'ATVD', 'BRPI', 'CENL', 'CEST', 'CM2SE', 'CHPE',
        'CAPR', 'AQTN', 'CENF', 'CHBO',
        'FRAC', 'CORS', 'GUAD', 'MART', 'REUN', 'TPOI', 'ANMA', 'LORR',
        'NORM', 'IDFR', 'TOUL', 'CODA', 'SAVO',
        'ILVI', 'COUE', 'FINI', 'LANG', 'MORB', 'NEST', 'L&HL', 'NORF',
        'NMPY', 'NORS', 'PRCA', 'PYGA', 'SRAL', 'SMED', 'VALF', 'CIB', 'LCL',
    ];

    private const OTHER_SHORTCODE = [
        'SOGE', 'BNP', 'LBP', 'HSBC', 'GCDN', 'CMDC', 'BARC',
        'OBC', 'ABN', 'RABO', 'PASCHI', 'FORTIS', 'CCOP', 'NATIXIS',
        'BPAL', 'BRED', 'BP', 'BPALC', 'BPACA', 'BPBFC', 'BPGO',
        'BPARA', 'BPDN', 'BPDS', 'BPM', 'BPO', 'BPRP', 'BPVF', 'CDE', 'CEPC',
        'CEBPL', 'CEPAC', 'CECA', 'CEAL', 'CEBFC', 'CEMP', 'CEGEE',
    ];

    private ObjectManager $entityManager;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CompanyGroupFixture::class,
        ];
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->entityManager = $manager;
        /** @var CompanyGroup $CAGroup */
        $CAGroup = $this->getReference('companyGroup/CA');

        // Main company
        /** @var User $user */
        $user    = $this->getReference(UserFixtures::ADMIN);
        $domain  = explode('@', $user->getEmail())[1];
        $company = $this->createCompany('CA Lending Services', Company::SHORT_CODE_KLS)->setEmailDomain($domain)->setCompanyGroup($CAGroup);
        $this->addReference(self::KLS, $company);

        $company = $this->createCompany('Crédit Agricole SA', Company::SHORT_CODE_CASA)
            ->setEmailDomain('credit-agricole-sa.fr')
            ->setCompanyGroup($CAGroup)
        ;
        $this->addReference(self::CASA, $company);

        // CA banks
        foreach (self::CA_SHORTCODE as $index => $shortCode) {
            $this->addReference(
                self::REFERENCE_PREFIX . $shortCode,
                $this->createCompany("CA Bank {$index}", $shortCode)->setCompanyGroup($CAGroup)
            );
        }

        // External bank
        $company = $this->createCompany('External Bank')->setShortCode(static::OTHER_SHORTCODE[0]);
        $this->addReference(self::COMPANY_EXTERNAL, $company);

        // Other external banks
        for ($i = 1; $i <= 5; ++$i) {
            $this->createCompany("External Bank {$i}")->setShortCode(static::OTHER_SHORTCODE[$i]);
        }

        /** @var Company $company */
        $company = $this->getReference(self::REFERENCE_PREFIX . self::CA_SHORTCODE[21]);
        $company->setDisplayName('Not signed Bank');
        $this->addReference(self::COMPANY_NOT_SIGNED, $company);

        /** @var Company $company */
        $company = $this->getReference(self::REFERENCE_PREFIX . self::CA_SHORTCODE[22]);
        $company->setDisplayName('Not signed no member Bank');
        $this->addReference(self::COMPANY_NOT_SIGNED_NO_MEMBERS, $company);

        /** @var Company $company */
        $company = $this->getReference(self::REFERENCE_PREFIX . self::CA_SHORTCODE[23]);
        $company->setDisplayName('Many staff');
        $this->addReference(self::COMPANY_MANY_STAFF, $company);

        $manager->flush();
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function createCompany(string $name = null, string $shortcode = null, int $status = CompanyStatus::STATUS_SIGNED): Company
    {
        $companyName = $name ?? $this->faker->company;
        $vatTypes    = Company::getPossibleVatTypes();
        // Works because Faker is set to Fr_fr
        $company = (new Company($companyName, $companyName, $this->faker->siren(false)))
            ->setBankCode((string) $this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($vatTypes[array_rand($vatTypes)])
        ;
        $this->forcePublicId($company, Urlizer::urlize($companyName));
        $companyStatus = new CompanyStatus($company, $status);
        $company->setCurrentStatus($companyStatus);

        $team1 = Team::createTeam('1', $company->getRootTeam());
        $this->entityManager->persist($team1);
        $team2 = Team::createTeam('2', $company->getRootTeam());
        $this->entityManager->persist($team2);

        $team21 = Team::createTeam('2-1', $team2);
        $this->entityManager->persist($team21);
        $team22 = Team::createTeam('2-2', $team2);
        $this->entityManager->persist($team22);

        $this->entityManager->persist($company);

        return $company;
    }
}
