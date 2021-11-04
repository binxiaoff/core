<?php

declare(strict_types=1);

namespace KLS\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;

class CompanyGroupFixtures extends AbstractFixtures
{
    public const CORPORATE               = 'company_group_tag_corporate';
    public const PUBLIC_COLLECTIVITY     = 'company_group_tag_public_collectivity';
    public const ENERGY                  = 'company_group_tag_energy';
    public const REAL_ESTATE_DEVELOPMENT = 'company_group_real_estate_development';
    public const PPP                     = 'company_group_tag_ppp';
    public const AGRICULTURE             = 'company_group_tag_agriculture';
    public const PRO                     = 'company_group_tag_pro';
    public const PATRIMONIAL             = 'company_group_tag_patrimonial';

    public function load(ObjectManager $manager)
    {
        $companyGroup = new CompanyGroup('Crédit Agricole');
        $manager->persist($companyGroup);
        $this->addReference('companyGroup/CA', $companyGroup);

        $codes = [
            self::CORPORATE               => 'corporate',
            self::PUBLIC_COLLECTIVITY     => 'public_collectivity',
            self::ENERGY                  => 'energy',
            self::REAL_ESTATE_DEVELOPMENT => 'real_estate_development',
            self::PPP                     => 'ppp',
            self::AGRICULTURE             => 'agriculture',
            self::PRO                     => 'pro',
            self::PATRIMONIAL             => 'patrimonial',
        ];

        foreach ($codes as $referenceName => $code) {
            $tag = new CompanyGroupTag($companyGroup, $code);
            $manager->persist($tag);
            $this->addReference($referenceName, $tag);
        }

        $manager->flush();
    }
}
