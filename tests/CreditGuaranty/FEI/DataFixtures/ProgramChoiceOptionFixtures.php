<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
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
        $programs = [
            $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED),
            $this->getReference(ProgramFixtures::REFERENCE_PAUSED),
        ];

        /** @var Program $program */
        foreach ($programs as $program) {
            foreach ($this->loadData() as $fieldReference => $descriptions) {
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

    private function loadData(): array
    {
        return [
            'field-' . FieldAlias::ACTIVITY_DEPARTMENT => [
                '75',
            ],
            'field-' . FieldAlias::ACTIVITY_COUNTRY => [
                'FR',
            ],
            'field-' . FieldAlias::AID_INTENSITY => [
                '0.20', '0.40', '0.60', '0.80',
            ],
            'field-' . FieldAlias::BORROWER_TYPE => [
                'Installé',
                'En reconversion',
                'Agriculture',
                'Apiculteur',
            ],
            'field-' . FieldAlias::COMPANY_NAF_CODE => [
                '0001A',
            ],
            'field-' . FieldAlias::EXPLOITATION_SIZE => [
                '42',
            ],
            'field-' . FieldAlias::INVESTMENT_COUNTRY => [
                'FR',
            ],
            'field-' . FieldAlias::INVESTMENT_DEPARTMENT => [
                '75',
            ],
            'field-' . FieldAlias::INVESTMENT_LOCATION => [
                'Paris',
                'Seine-et-Marne',
                'Val-de-Marne',
            ],
            'field-' . FieldAlias::INVESTMENT_THEMATIC => [
                'Renouvellement et installation',
                'Mieux répondre / renforcer',
                'Transformation',
                'Accompagner',
                'Mettre à niveau',
            ],
            'field-' . FieldAlias::LEGAL_FORM => [
                'SARL', 'SAS', 'SASU', 'EURL', 'SA', 'SELAS',
            ],
            'field-' . FieldAlias::LOAN_NAF_CODE => [
                '0001A',
            ],
            'field-' . FieldAlias::LOAN_TYPE => [
                'term_loan', 'short_term', 'revolving_credit', 'stand_by', 'signature_commitment',
            ],
            'field-' . FieldAlias::PRODUCT_CATEGORY_CODE => [
                '1 - ANIMAUX',
                '6 - PLANTES',
                '7 - LÉGUMES',
                '8 - FRUITS',
                '10 - CÉRÉALES',
            ],
            'field-' . FieldAlias::TARGET_TYPE => [
                'Cible A',
                'Cible B',
            ],
        ];
    }
}
