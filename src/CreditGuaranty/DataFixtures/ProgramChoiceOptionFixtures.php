<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;

    public function __construct(TokenStorageInterface $tokenStorage, FieldRepository $fieldRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $lists = [
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

        $fields = [];
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            foreach ($lists as $fieldAlias => $choices) {
                $nbChoices = count($choices);
                if (false === isset($fields[$fieldAlias])) {
                    $fields[$fieldAlias] = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);
                }

                for ($i = 0; $i <= random_int(0, $nbChoices - 1); ++$i) {
                    $manager->persist(new ProgramChoiceOption($program, $choices[$i], $fields[$fieldAlias]));
                }
            }
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
            FieldFixtures::class,
        ];
    }
}
