<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const FIELDS = [
        FieldAlias::BORROWER_TYPE => [
            'Installé depuis moins de 7 ans', 'Installé depuis plus de 7 ans',
            'Installé depuis moins de 10 ans', 'Installé depuis plus de 10 ans',
            'En reconversion Bio', 'Jeune agriculteur de moins de 30 ans',
            'Agriculture durable', 'Agriculture céréalière', 'Agriculture bovine',
            'Apiculteur', 'Exploitant céréalier', 'Ostréiculteur',
            'Producteur de lait', 'Vignoble',
        ],
        FieldAlias::ACTIVITY_COUNTRY => [
            'FR',
        ],
        FieldAlias::INVESTMENT_COUNTRY => [
            'FR',
        ],
    ];

    private FieldRepository $fieldRepository;

    public function __construct(TokenStorageInterface $tokenStorage, FieldRepository $fieldRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            FieldFixtures::class,
            ProgramFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            foreach (self::FIELDS as $fieldAlias => $descriptions) {
                /** @var Field $field */
                $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

                foreach ($descriptions as $description) {
                    $programChoiceOption = new ProgramChoiceOption($program, $description, $field);
                    $manager->persist($programChoiceOption);
                }
            }
        }

        $manager->flush();
    }
}
