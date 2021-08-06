<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\CompanyGroups;

use Doctrine\Persistence\ObjectManager;
use ReflectionException;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

abstract class AbstractCompanyGroupFixtures extends AbstractFixtures
{
    /**
     * @throws ReflectionException
     */
    final public function load(ObjectManager $manager)
    {
        $companyGroup = new CompanyGroup($this->getName());

        $reference = 'companyGroup:' . $this->getName();

        $this->addReference($reference, $companyGroup);

        $this->setPublicId($companyGroup, $reference);

        foreach ($this->getTags($companyGroup) as $tag) {
            $this->addReference($reference . '_tag:' . $tag->getCode(), $tag);
            $manager->persist($tag);
        }

        $manager->persist($companyGroup);

        $manager->flush();
    }

    abstract protected function getName(): string;

    /**
     * @return array|CompanyGroupTag[]
     */
    abstract protected function getTags(CompanyGroup $companyGroup): array;
}
