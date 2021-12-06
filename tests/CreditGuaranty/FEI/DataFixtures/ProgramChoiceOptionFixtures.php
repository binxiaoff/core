<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    // user-defined list type fields
    private const FIELDS = [
        'field-activity_department' => [
            '75',
        ],
        'field-activity_country' => [
            'FR',
        ],
        'field-aid_intensity' => [
            '0.20', '0.40', '0.60', '0.80',
        ],
        'field-borrower_type' => [
            'Installé',
            'En reconversion',
            'Agriculture',
            'Apiculteur',
        ],
        'field-company_naf_code' => [
            '0001A',
        ],
        'field-exploitation_size' => [
            '42',
        ],
        'field-investment_country' => [
            'FR',
        ],
        'field-investment_department' => [
            '75',
        ],
        'field-investment_location' => [
            'Paris',
        ],
        'field-investment_thematic' => [
            'Renouvellement et installation',
            'Mieux répondre / renforcer',
            'Transformation',
            'Accompagner',
            'Mettre à niveau',
        ],
        'field-legal_form' => [
            'SARL', 'SAS', 'SASU', 'EURL', 'SA', 'SELAS',
        ],
        'field-loan_naf_code' => [
            '0001A',
        ],
        'field-loan_type' => [
            'term_loan', 'short_term', 'revolving_credit', 'stand_by', 'signature_commitment',
        ],
        'field-product_category_code' => [
            '1 - ANIMAUX',
            '6 - PLANTES',
            '7 - LÉGUMES',
            '8 - FRUITS',
            '10 - CÉRÉALES',
        ],
    ];

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
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);

        foreach (self::FIELDS as $fieldReference => $descriptions) {
            /** @var Field $field */
            $field = $this->getReference($fieldReference);

            foreach ($descriptions as $description) {
                $programChoiceOption = new ProgramChoiceOption($program, $description, $field);
                $manager->persist($programChoiceOption);
            }
        }

        $manager->flush();
    }
}
