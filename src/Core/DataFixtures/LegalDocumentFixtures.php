<?php

declare(strict_types=1);

namespace KLS\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\Entity\AcceptationsLegalDocs;
use KLS\Core\Entity\LegalDocument;
use ReflectionException;

class LegalDocumentFixtures extends AbstractFixtures
{
    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        $serviceDoc = (new LegalDocument(LegalDocument::TYPE_SERVICE_TERMS, 'Service Document', \nl2br($this->faker->paragraphs(50, true))));
        $this->forcePublicId($serviceDoc, '3ac531f2-14e9-11ea-8b64-0226455cbcab');
        $this->forceId($manager, $serviceDoc, LegalDocument::CURRENT_SERVICE_TERMS_ID);
        $manager->persist($serviceDoc);

        // We auto-validate the document for this user because it is used for
        // automated end-to-end tests
        $operatorUser       = $this->getReference(UserFixtures::OPERATOR);
        $operatorAcceptance = new AcceptationsLegalDocs($operatorUser, $serviceDoc);
        $manager->persist($operatorAcceptance);

        $manager->flush();
        $this->restoreAutoIncrement($serviceDoc, $manager);
    }
}
