<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\{Clients, Company, CompanyStatus};

class CompanyFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const CALS = 'COMPANY_CALS';
    public const COMPANY1 = 'COMPANY1';
    public const COMPANY2 = 'COMPANY2';
    public const COMPANY3 = 'COMPANY3';
    public const COMPANY4 = 'COMPANY4';
    public const COMPANY5 = 'COMPANY5';
    public const COMPANY_MANY_STAFF = 'COMPANY_MANY_STAFF';
    public const COMPANY_NOT_SIGNED = 'COMPANY_NOT_SIGNED';
    public const COMPANY_NOT_SIGNED_NO_MEMBERS = 'COMPANY_NOT_SIGNED_NO_MEMBERS';
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

    private const CA_SHORTCODE = [
        'ALVO', 'ATVD', 'BRPI', 'CENL', 'CEST', 'CM2SE', 'CHPE',
        'CAPR', 'AQTN', 'CENF', 'CHBO',
        'FRAC', 'CORS', 'GUAD', 'MART', 'REUN', 'TPOI', 'ANMA', 'LORR',
        'NORM', 'IDFR', 'TOUL', 'CODA', 'SAVO',
        'ILVI', 'COUE', 'FINI', 'LANG', 'MORB', 'NEST', 'L&HL', 'NORF',
        'NMPY', 'NORS', 'PRCA', 'PYGA', 'SRAL', 'SMED', 'VALF', 'CIB', 'CDM', 'CASA', 'LCL',
    ];
    private const OTHER_SHORTCODE = [
        'SOGE', 'BNP', 'LBP', 'HSBC', 'GCDN', 'CMDC', 'BARC',
        'OBC', 'ABN', 'RABO', 'PASCHI', 'FORTIS', 'CCOP', 'NATIXIS',
        'BPAL', 'BRED', 'BP', 'BPALC', 'BPACA', 'BPBFC', 'BPGO',
        'BPARA', 'BPDN', 'BPDS', 'BPM', 'BPO', 'BPRP', 'BPVF', 'CDE', 'CEPC',
        'CEBPL', 'CEPAC', 'CECA', 'CEAL', 'CEBFC', 'CEMP', 'CEGEE',
    ];

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /**
     * @param TokenStorageInterface  $tokenStorage
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct($tokenStorage);
    }

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
        $user    = $this->getReference(UserFixtures::ADMIN);
        $domain  = explode('@', $user->getEmail())[1];
        $company = $this->createCompany(Company::COMPANY_NAME_CALS, 'CALS')->setEmailDomain($domain)->setGroupName('Crédit Agricole');
        $this->addReference(self::CALS, $company);

        // Fake bank
        for ($i = 1; $i <= 5; $i++) {
            $company = $this->createCompany("CA Bank $i", static::CA_SHORTCODE[$i])->setGroupName('Crédit Agricole');
            $this->addReference(self::COMPANIES[$i - 1], $company);
        }

        for ($i = 6; $i <= 20; $i++) {
            $this->createCompany("CA Bank $i", static::CA_SHORTCODE[$i])->setGroupName('Crédit Agricole');
        }

        // External bank
        $company = $this->createCompany('External Bank')->setShortCode(static::OTHER_SHORTCODE[0]);
        $this->addReference(self::COMPANY_EXTERNAL, $company);

        // Fake bank
        for ($i = 1; $i <= 5; $i++) {
            $this->createCompany("External Bank $i")->setShortCode(static::OTHER_SHORTCODE[$i]);
        }

        $this->addReference(
            self::COMPANY_NOT_SIGNED,
            $this->createCompany('Not signed Bank', static::CA_SHORTCODE[21], CompanyStatus::STATUS_PROSPECT)->setGroupName('Crédit Agricole')
        );
        $this->addReference(
            self::COMPANY_NOT_SIGNED_NO_MEMBERS,
            $this->createCompany('Not signed no member Bank', static::CA_SHORTCODE[22], CompanyStatus::STATUS_PROSPECT)->setGroupName('Crédit Agricole')
        );

        $this->addReference(
            self::COMPANY_MANY_STAFF,
            $this->createCompany('Many staff', static::CA_SHORTCODE[23])->setGroupName('Crédit Agricole')
        );

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
        $company     = (new Company($companyName, $companyName))
            ->setBankCode((string) $this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($this->faker->vat);
        $this->forcePublicId($company, Urlizer::urlize($companyName));
        $companyStatus = new CompanyStatus($company, $status);
        $company->setCurrentStatus($companyStatus);

        $this->entityManager->persist($company);

        return $company;
    }
}
