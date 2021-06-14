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
    private const FIELDS = [
        'field-borrower_type' => [
            'Installé',
            'En reconversion',
            'Agriculture',
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
        /** @var Field $field */
        $field = $this->getReference('field-borrower_type');

        foreach (self::FIELDS['field-borrower_type'] as $item) {
            $programChoiceOption = new ProgramChoiceOption($program, $item, $field);
            $manager->persist($programChoiceOption);
        }

        $manager->flush();
    }
}
