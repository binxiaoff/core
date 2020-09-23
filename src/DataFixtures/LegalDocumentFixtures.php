<?php

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\LegalDocument;

class LegalDocumentFixtures extends AbstractFixtures
{

    /**
     * @param ObjectManager $manager
     *
     * @throws \ReflectionException
     */
    public function load(ObjectManager $manager)
    {
        $serviceDoc = (new LegalDocument())
            ->setTitle('Service Document')
            ->setContent(nl2br($this->faker->paragraphs(50, true)))
            ->setType(LegalDocument::CURRENT_SERVICE_TERMS);
        $this->disableAutoIncrement($serviceDoc, $manager);
        $this->forcePublicId($serviceDoc, '3ac531f2-14e9-11ea-8b64-0226455cbcab');
        $this->forceId($serviceDoc, 2);
        $manager->persist($serviceDoc);
        $manager->flush();
    }
}
