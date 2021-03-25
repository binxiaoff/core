<?php

namespace Unilend\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\Entity\AcceptationsLegalDocs;
use Unilend\Core\Entity\LegalDocument;

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
        $this->forcePublicId($serviceDoc, '3ac531f2-14e9-11ea-8b64-0226455cbcab');
        $this->forceId($manager, $serviceDoc, 2);
        $manager->persist($serviceDoc);

        // We auto-validate the document for this user because it is used for
        // automated end-to-end tests
        $operatorUser = $this->getReference(UserFixtures::OPERATOR);
        $operatorAcceptance = new AcceptationsLegalDocs($operatorUser, $serviceDoc);
        $manager->persist($operatorAcceptance);

        $manager->flush();
        $this->restoreAutoIncrement($serviceDoc, $manager);
    }
}
