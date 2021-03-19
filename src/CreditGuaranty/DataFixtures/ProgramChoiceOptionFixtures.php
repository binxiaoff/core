<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\{AbstractFixtures, DumpedDataFixture};
use Unilend\CreditGuaranty\Entity\{Constant\FieldAlias, Program, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param FieldRepository       $fieldRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, FieldRepository $fieldRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $programReferences = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        $choices = [
            FieldAlias::BORROWER_TYPE => [
                'Installé depuis plus de 7 ans', 'Installé depuis moins de 7 ans',
                'En reconversion Bio', 'Agriculture céréalière',
                'Agriculture bovine', 'Producteur de lait',
                'Exploitant céréalier', 'Ostréiculteur',
                'Apiculteur', 'Agriculture durable',
                'Vignoble', 'Jeune agriculteur de moins de 30 ans',
                'Installé depuis moins de 10 ans', 'Installé depuis plus de 10 ans',
            ],
        ];
        foreach ($choices as $type => $typeChoices) {
            $this->setProgramChoiceOptions($manager, $programReferences, $typeChoices, $type);
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramFixtures::class,
            DumpedDataFixture::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     * @param array         $programReferences
     * @param array         $choices
     * @param string        $fieldAlias
     */
    private function setProgramChoiceOptions(ObjectManager $manager, array $programReferences, array $choices, string $fieldAlias)
    {
        $nbChoices = count($choices);
        /** @var EligibilityCriteria $eligibilityCriteria **/
        $field     = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);

            for ($i = 0; $i <= rand(0, $nbChoices - 1); $i++) {
                $manager->persist(new ProgramChoiceOption($program, $choices[$i], $field));
            }
        }
    }
}
