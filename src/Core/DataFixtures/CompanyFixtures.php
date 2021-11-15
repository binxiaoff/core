<?php

declare(strict_types=1);

namespace KLS\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use ReflectionException;

class CompanyFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const REFERENCE_PREFIX = 'company:';
    public const KLS              = self::REFERENCE_PREFIX . Company::SHORT_CODE_KLS;
    public const CASA             = self::REFERENCE_PREFIX . Company::SHORT_CODE_CASA;

    public const COMPANY_MANY_STAFF = 'COMPANY_MANY_STAFF';
    public const COMPANY_EXTERNAL   = 'COMPANY_EXTERNAL';

    public const COMPANIES = [
        self::REFERENCE_PREFIX . self::CA_SHORTCODE['CA des Savoie'],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE['CA Toulouse 31'],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE['CA Anjou et Maine'],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE['CA Aquitaine'],
        self::REFERENCE_PREFIX . self::CA_SHORTCODE['CA Atlantique Vendée'],
        self::COMPANY_EXTERNAL,
        self::COMPANY_NOT_SIGNED,
    ];

    public const CA_SHORTCODE = [
        'CA Alpes Provence'                => 'CAPR',
        'CA Alsace Vosges'                 => 'ALVO',
        'CA Anjou et Maine'                => 'ANMA',
        'CA Aquitaine'                     => 'AQTN',
        'CA Atlantique Vendée'             => 'ATVD',
        'CA Brie Picardie'                 => 'BRPI',
        'CA Centre-Est'                    => 'CEST',
        'CA Centre France'                 => 'CENF',
        'CA Centre Loire'                  => 'CENL',
        'CA Centre Ouest'                  => 'COUE',
        'CA Champagne-Bourgogne'           => 'CHBO',
        'CA Charente Maritime Deux-Sèvres' => 'CM2SE',
        'CA Charente-Périgord'             => 'CHPE',
        'CA Corse'                         => 'CORS',
        'CA Côtes d’Armor'                 => 'CODA',
        'CA Normandie'                     => 'NORM',
        'CA des Savoie'                    => 'SAVO',
        'CA Finistère'                     => 'FINI',
        'CA Franche-Comté'                 => 'FRAC',
        'CA Guadeloupe'                    => 'GUAD',
        'CA Ille-et-Vilaine'               => 'ILVI',
        'CA Languedoc'                     => 'LANG',
        'CA Loire Haute-Loire'             => 'L&HL',
        'CA Lorraine'                      => 'LORR',
        'CA Martinique-Guyane'             => 'MART',
        'CA Morbihan'                      => 'MORB',
        'CA Nord de France'                => 'NORF',
        'CA Nord Est'                      => 'NEST',
        'CA Nord Midi Pyrénées'            => 'NMPY',
        'CA Normandie-Seine'               => 'NORS',
        'CA Paris et Ile-de-France'        => 'IDFR',
        'CA Provence Côte d’Azur'          => 'PRCA',
        'CA Pyrénées Gascogne'             => 'PYGA',
        'CA La Réunion'                    => 'REUN',
        'CA Sud Rhône Alpes'               => 'SRAL',
        'CA Sud Méditerranée'              => 'SMED',
        'CA Toulouse 31'                   => 'TOUL',
        'CA Touraine Poitou'               => 'TPOI',
        'CA Val de France'                 => 'VALF',
        'LCL'                              => 'LCL',
        'CA-CIB'                           => 'CIB',
        'Unifergie'                        => 'CALF',
    ];

    private const COMPANY_NOT_SIGNED            = 'COMPANY_NOT_SIGNED';
    private const COMPANY_NOT_SIGNED_NO_MEMBERS = 'COMPANY_NOT_SIGNED_NO_MEMBERS';

    private const OTHER_SHORTCODE = [
        'BNP Paribas'                                  => 'BNP',
        'La Banque Postale'                            => 'LBP',
        'HSBC'                                         => 'HSBC',
        'Crédit du Nord'                               => 'CDN',
        'Barclays'                                     => 'BARC',
        'Banque Neuflize OBC'                          => 'OBC',
        'ABN AMRO'                                     => 'ABN',
        'RABOBANK'                                     => 'RABO',
        'Monte Paschi'                                 => 'PASCHI',
        'FORTIS BANQUE'                                => 'FORTIS',
        'Crédit Cooperatif'                            => 'CCOP',
        'Natixis'                                      => 'NATIXIS',
        'La banque Palatine'                           => 'BPAL',
        'BRED'                                         => 'BRED',
        'Banque Populaire Alsace Lorraine Champagne'   => 'BPALC',
        'Banque Populaire Aquitaine Centre Atlantique' => 'BPACA',
        'Banque Populaire Bourgogne franche comte'     => 'BPBFC',
        'Banque Populaire Grand Ouest'                 => 'BPGO',
        'Banque Populaire Auvergne Rhône Alpes'        => 'BPARA',
        'Banque Populaire du Nord'                     => 'BPDN',
        'Banque Populaire du Sud'                      => 'BPDS',
        'Banque Populaire Méditéranée'                 => 'BPM',
        'Banque Populaire Occitane'                    => 'BPO',
        'Banque Populaire Rives de Paris'              => 'BPRP',
        'Banque Populaire Val de France'               => 'BPVF',
        "Caisse d'Epargne Aquitaine Poitou-Charentes"  => 'CEPC',
        "Caisse d'Epargne Bretagne Pays de Loire"      => 'CEBPL',
        "Caisse d'Epargne CEPAC"                       => 'CEPAC',
        "Caisse d'Epargne Cote d'Azur"                 => 'CECA',
        "Caisse d'Epargne d'Auvergne et du Limousin"   => 'CEAL',
        "Caisse d'Epargne de Bourgogne Franche-Comte"  => 'CEBFC',
        "Caisse d'Epargne de Midi-Pyrenees"            => 'CEMP',
        "Caisse d'Epargne Grand Est Europe"            => 'CEGEE',
        "Caisse d'Epargne Hauts de France"             => 'CEHF',
        "Caisse d'Epargne Ile-de-France"               => 'CEIDF',
        "Caisse d'Epargne Languedoc-Roussillon"        => 'CELR',
        "Caisse d'Epargne Loire Drome Ardeche"         => 'CELDA',
        "Caisse d'Epargne Loire-Centre"                => 'CELC',
        "Caisse d'Epargne Normandie"                   => 'CEN',
        "Caisse d'Epargne Rhône Alpes"                 => 'CERA',
        'Crédit Mutuel Anjou'                          => 'CMAN',
        'Crédit Mutuel du Centre'                      => 'CMC',
        'Crédit Mutuel Centre-Est Europe'              => 'CMCEE',
        'Crédit Mutuel Dauphine Vivarais'              => 'CMDV',
        'Crédit Mutuel Ile de France'                  => 'CMIDF',
        'Crédit Mutuel Loire-Atlantique Centre-Ouest'  => 'CMLACO',
        'Crédit Mutuel Massif Central'                 => 'CMMC',
        'Crédit Mutuel Méditéranéen'                   => 'CMM',
        'Crédit Mutuel Midi-Atlantique'                => 'CMMA',
        'Crédit Mutuel Normandie'                      => 'CMN',
        'Crédit Mutuel Savoie Mont Blanc'              => 'CMSMB',
        'Crédit Mutuel Sud-Est'                        => 'CMSE',
        'Crédit Mutuel de Bretagne'                    => 'CMB',
        'Crédit Mutuel du Sud-Ouest'                   => 'CMSO',
        'BPI France'                                   => 'BPIF',
        'Banque Courtois'                              => 'COUR',
        'Société Marseillaise de Crédit'               => 'SMC',
        'Banque Kolb'                                  => 'KOLB',
        'Banque Tarneaud'                              => 'TARN',
        'Banque Rhône-Alpes'                           => 'BRAL',
        'Banque Nuger'                                 => 'NUGE',
        'Banque Laydernier'                            => 'LAYD',
        'Arkéa Banque Entreprises et Institutionnels'  => 'ARKEI',
        'Crédit Mutuel Maine-Anjou Basse Normandie'    => 'CMMAN',
        'Crédit Mutuel Nord Europe'                    => 'CMNE',
        'Crédit Mutuel Océan'                          => 'CMOC',
        'Crédit Mutuel Antilles Guyane'                => 'CMAG',
        'BECM'                                         => 'BECM',
        'CIC Nord Ouest'                               => 'CICNO',
        'CIC Ouest'                                    => 'CICO',
        'CIC Est'                                      => 'CICE',
        'CIC Sud Ouest'                                => 'CICSO',
        'CIC Lyonnaise de Banque'                      => 'CICLY',
        'CIC Ile de France'                            => 'CICIDF',
        'Auxifip'                                      => 'AUXI',
        'Finamur'                                      => 'FINAM',
    ];

    private ObjectManager $entityManager;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CompanyGroupFixtures::class,
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
        $domain  = \explode('@', $user->getEmail())[1];
        $company = $this->createCompany('KLS', Company::SHORT_CODE_KLS)
            ->setEmailDomain($domain)
            ->setCompanyGroup($CAGroup)
        ;
        $this->addReference(self::KLS, $company);

        $company = $this->createCompany('Crédit Agricole SA', Company::SHORT_CODE_CASA)
            ->setEmailDomain('credit-agricole-sa.fr')
            ->setCompanyGroup($CAGroup)
        ;
        $this->addReference(self::CASA, $company);

        // CA banks
        foreach (self::CA_SHORTCODE as $name => $shortCode) {
            $this->addReference(
                self::REFERENCE_PREFIX . $shortCode,
                $this->createCompany($name, $shortCode)->setCompanyGroup($CAGroup)
            );
        }

        // External bank
        $company = $this->createCompany('Société Générale')->setShortCode('SOGE');
        $this->addReference(self::COMPANY_EXTERNAL, $company);

        // Other external banks
        foreach (self::OTHER_SHORTCODE as $name => $shortCode) {
            $this->createCompany($name)->setShortCode($shortCode);
        }

        $company = $this->createCompany('Not signed Bank')->setShortCode('NOTSIGNED');
        $this->addReference(self::COMPANY_NOT_SIGNED, $company);
        $manager->persist($company);

        $company = $this->createCompany('unsigned no member Bank')->setShortCode('NOMEMBER');
        $this->addReference(self::COMPANY_NOT_SIGNED_NO_MEMBERS, $company);

        $company = $this->createCompany('Many staff')->setShortCode('MANYSTAFF');
        $this->addReference(self::COMPANY_MANY_STAFF, $company);

        $manager->flush();
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function createCompany(
        string $name = null,
        string $shortcode = null,
        int $status = CompanyStatus::STATUS_SIGNED
    ): Company {
        $companyName = $name ?? $this->faker->company;
        $vatTypes    = Company::getPossibleVatTypes();
        // Works because Faker is set to Fr_fr
        $company = (new Company($companyName, $this->faker->siren(false)))
            ->setLegalName($companyName)
            ->setClientNumber((string) $this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($vatTypes[\array_rand($vatTypes)])
        ;

        $this->forcePublicId($company, Urlizer::urlize(
            \str_replace(
                ['Banque Populaire', "Caisse d'Epargne", 'Crédit Mutuel', 'Banque Entreprises et Institutionnels'],
                ['BP', 'CE', 'CM', ''],
                $companyName
            )
        ));
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
