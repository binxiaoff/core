<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\{AbstractFixtures, DumpedDataFixture};
use Unilend\CreditGuaranty\Entity\{Constant\FieldAlias, Field, Program, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;

    private const FAKE_LISTS = [
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
        $fields = $this->fieldRepository->findBy(['type' => Field::TYPE_LIST]);

        foreach ($fields as $field) {
            $this->setProgramChoiceOptions($manager, $programReferences, $field);
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
     * @param Field         $field
     */
    private function setProgramChoiceOptions(ObjectManager $manager, array $programReferences, Field $field)
    {
        if (false === \is_array($field->getPredefinedItems())) {
            $items = isset(self::FAKE_LISTS[$field->getFieldAlias()]) ? self::FAKE_LISTS[$field->getFieldAlias()] : [];
            $nbItems = count($items);

            if (0 < $nbItems) {
                foreach ($programReferences as $programReference) {
                    /** @var Program $program */
                    $program = $this->getReference($programReference);
                    for ($i = 0; $i <= rand(0, $nbItems - 1); $i++) {
                        $manager->persist(new ProgramChoiceOption($program, $items[$i], $field));
                    }
                }
            }
        }
    }
}
