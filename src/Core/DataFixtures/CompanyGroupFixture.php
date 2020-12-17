<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\CompanyGroup;

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

        $manager->flush();
    }
}
