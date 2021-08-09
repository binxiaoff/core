<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\Entity\Field;
use KLS\CreditGuaranty\Entity\Program;
use KLS\CreditGuaranty\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\Repository\FieldRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    // user-defined list type fields
    private const FIELDS = [
        FieldAlias::AID_INTENSITY => [
            '0.20', '0.40', '0.60', '0.80',
        ],
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
        FieldAlias::INVESTMENT_LOCATION => [
            'Paris', 'Nantes', 'Lyon', 'Marseille', 'Nice',
        ],
        FieldAlias::COMPANY_NAF_CODE => [
            '0111Z', '0121Z', '0141Z', '0142Z',
        ],
        FieldAlias::LOAN_NAF_CODE => [
            '0111Z', '0121Z', '0141Z', '0142Z',
        ],
        FieldAlias::EXPLOITATION_SIZE => [
            '64', '512', '1024', '2048',
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
