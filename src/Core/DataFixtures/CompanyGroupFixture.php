<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;

class CompanyGroupFixture extends AbstractFixtures
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $companyGroup = new CompanyGroup('Crédit Agricole');

        $this->addReference('companyGroup/CA', $companyGroup);

        $manager->persist($companyGroup);

        $codes = [
            'corporate', 'public_collectivity', 'energy', 'real_estate_development', 'ppp', 'agriculture', 'pro', 'patrimonial',
        ];

        foreach ($codes as $code) {
            $tag = new CompanyGroupTag($companyGroup, $code);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}
