<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    // user-defined list type fields
    private const FIELDS = [
        'field-borrower_type' => [
            'Installé',
            'En reconversion',
            'Agriculture',
            'Apiculteur',
        ],
        'field-company_naf_code' => [
            '0001A',
        ],
        'field-loan_naf_code' => [
            '0001A',
        ],
        'field-exploitation_size' => [
            '42',
        ],
        'field-activity_country' => [
            'FR',
        ],
        'field-investment_country' => [
            'FR',
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
