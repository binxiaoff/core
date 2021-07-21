<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Faker\Generator;
use ReflectionException;
use Unilend\Core\Entity\AcceptationsLegalDocs;
use Unilend\Core\Entity\LegalDocument;
use Unilend\Core\Entity\User;

class LegalDocumentFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private Generator $faker;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create('fr_FR');

        $serviceDoc = (new LegalDocument(LegalDocument::TYPE_SERVICE_TERMS, 'Service Document', \nl2br($this->faker->paragraphs(50, true))));
        $this->setPublicId($serviceDoc, '3ac531f2-14e9-11ea-8b64-0226455cbcab');
        $this->forceId($manager, $serviceDoc, LegalDocument::CURRENT_SERVICE_TERMS_ID);
        $manager->persist($serviceDoc);

        // We auto-validate the document for this user because it is used for
        // automated end-to-end tests
        /** @var User $operatorUser */
        $operatorUser       = $this->getReference('user-1');
        $operatorAcceptance = new AcceptationsLegalDocs($operatorUser, $serviceDoc);
        $manager->persist($operatorAcceptance);
        $manager->flush();

        $this->restoreAutoIncrement($serviceDoc, $manager);
    }
}
