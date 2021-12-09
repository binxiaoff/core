<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\LoanType;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const ALL = [
        FieldAlias::ACTIVITY_COUNTRY => [
            'FR' => 'FR',
        ],
        FieldAlias::ACTIVITY_DEPARTMENT => [
            '75' => '75',
            '77' => '77',
            '94' => '94',
        ],
        FieldAlias::ADDITIONAL_GUARANTY => [
            'A' => 'Garantie A',
            'B' => 'Garantie B',
        ],
        FieldAlias::AGRICULTURAL_BRANCH => [
            'A' => 'Branch A',
            'B' => 'Branch B',
            'C' => 'Branch C',
            'D' => 'Branch D',
        ],
        FieldAlias::AID_INTENSITY => [
            '0.20' => '0.20',
            '0.40' => '0.40',
            '0.60' => '0.60',
            '0.80' => '0.80',
        ],
        FieldAlias::BORROWER_TYPE => [
            'Installé -7a'         => 'Installé depuis moins de 7 ans',
            'Installé +7a'         => 'Installé depuis plus de 7 ans',
            'Installé -10a'        => 'Installé depuis moins de 10 ans',
            'Installé +10a'        => 'Installé depuis plus de 10 ans',
            'Bio'                  => 'En reconversion Bio',
            'Agri -30a'            => 'Jeune agriculteur de moins de 30 ans',
            'Agri durable'         => 'Agriculture durable',
            'Agri céréalière'      => 'Agriculture céréalière',
            'Agri bovine'          => 'Agriculture bovine',
            'Agri'                 => 'Apiculteur',
            'Exploitant céréalier' => 'Exploitant céréalier',
            'Ostréiculteur'        => 'Ostréiculteur',
            'Producteur de lait'   => 'Producteur de lait',
            'Vignoble'             => 'Vignoble',
        ],
        FieldAlias::COMPANY_NAF_CODE => [
            '0111Z' => '0111Z',
            '0121Z' => '0121Z',
            '0141Z' => '0141Z',
            '0142Z' => '0142Z',
        ],
        FieldAlias::EXPLOITATION_SIZE => [
            '64'   => '64',
            '512'  => '512',
            '1024' => '1024',
            '2048' => '2048',
        ],
        FieldAlias::FINANCING_OBJECT_TYPE => [
            'Achat de matériel' => 'Achat de matériel',
            'Investissement'    => 'Investissement',
        ],
        FieldAlias::INVESTMENT_COUNTRY => [
            'FR' => 'FR',
        ],
        FieldAlias::INVESTMENT_DEPARTMENT => [
            '75' => '75',
            '77' => '77',
            '94' => '94',
        ],
        FieldAlias::INVESTMENT_LOCATION => [
            'Paris'     => 'Paris',
            'Nantes'    => 'Nantes',
            'Lyon'      => 'Lyon',
            'Marseille' => 'Marseille',
            'Nice'      => 'Nice',
        ],
        FieldAlias::INVESTMENT_THEMATIC => [
            'Renouvellement' => 'Renouvellement des générations et installation des nouveaux entrants dans le cadre '
                . 'd\'un projet agro-écologique ou d\'un projet générateur de valeur ajoutée et/ou d\'emplois',
            'Mieux répondre aux attentes' => 'Mieux répondre aux attentes des consommateurs / ' .
                'renforcer les filières de qualité, la contractualisation amont-aval' .
                ' et en encourageant l\'ancrage territorial et les circuits de proximité.',
            'Transformation' => 'Transformation des modèles agricoles pour une meilleure performance '
                . 'économique, sociale, environnementale et sanitaire et également à améliorer '
                . 'l\'innovation au sein des exploitations',
            'Accompagner'     => 'Accompagner la diversification des activités et des revenus des exploitations',
            'Mettre à niveau' => 'Mettre à niveau des actifs ne répondant plus aux meilleures ' .
                'pratiques internationales, afin de promouvoir l’utilisation efficace des ressources ' .
                'telles que l’énergie, la chaleur et l’eau',
        ],
        FieldAlias::INVESTMENT_TYPE => [
            'A' => 'Type investissement A',
            'B' => 'Type investissement B',
            'C' => 'Type investissement C',
            'D' => 'Type investissement D',
            'E' => 'Type investissement E',
        ],
        FieldAlias::LEGAL_FORM => [
            LegalForm::SARL  => LegalForm::SARL,
            LegalForm::SAS   => LegalForm::SAS,
            LegalForm::SASU  => LegalForm::SASU,
            LegalForm::EURL  => LegalForm::EURL,
            LegalForm::SA    => LegalForm::SA,
            LegalForm::SELAS => LegalForm::SELAS,
        ],
        FieldAlias::LOAN_NAF_CODE => [
            '0111Z' => '0111Z',
            '0121Z' => '0121Z',
            '0141Z' => '0141Z',
            '0142Z' => '0142Z',
        ],
        FieldAlias::LOAN_TYPE => [
            LoanType::TERM_LOAN            => LoanType::TERM_LOAN,
            LoanType::SHORT_TERM           => LoanType::SHORT_TERM,
            LoanType::REVOLVING_CREDIT     => LoanType::REVOLVING_CREDIT,
            LoanType::STAND_BY             => LoanType::STAND_BY,
            LoanType::SIGNATURE_COMMITMENT => LoanType::SIGNATURE_COMMITMENT,
        ],
        FieldAlias::PRODUCT_CATEGORY_CODE => [
            '1'       => '1 - ANIMAUX VIVANTS',
            '6'       => '6 - PLANTES VIVANTES ET PRODUITS DE LA FLORICULTURE',
            '7'       => '7 - LÉGUMES, PLANTES, RACINES ET TUBERCULES ALIMENTAIRES',
            '8'       => '8 - FRUITS COMESTIBLES; ÉCORCES D’AGRUMES OU DE MELONS',
            '10'      => '10 - CÉRÉALES',
            '401'     => '401 - Lait et crème de lait, non concentrés ni additionnés de sucre ou d’autres édulcorants',
            '406'     => '406 - Fromages et caillebotte',
            '8105000' => '8105000 - Kiwis, frais',
        ],
        FieldAlias::TARGET_TYPE => [
            'A' => 'Cible A',
            'B' => 'Cible B',
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
        foreach ($this->loadData() as $data) {
            /** @var Program $program */
            foreach ($data['programs'] as $program) {
                /** @var Field $field */
                foreach ($data['fields'] as $field) {
                    // ignore pre-defined list type fields
                    if (FieldAlias::LOAN_PERIODICITY === $field->getFieldAlias()) {
                        continue;
                    }

                    foreach (self::ALL[$field->getFieldAlias()] as $description) {
                        $programChoiceOption = new ProgramChoiceOption($program, $description, $field);
                        $manager->persist($programChoiceOption);
                    }
                }
            }
        }

        $manager->flush();
    }

    /**
     * These data must be almost the same as ProgramEligibilityFixtures.
     */
    private function loadData(): iterable
    {
        yield 'programs with all configured options' => [
            'programs' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_COMMERCIALIZED,
                ProgramFixtures::PROGRAM_CORPORATE_PAUSED,
                ProgramFixtures::PROGRAM_CORPORATE_ARCHIVED,
            ]),
            'fields' => $this->fieldRepository->findBy(['tag' => Field::TAG_ELIGIBILITY, 'type' => Field::TYPE_LIST]),
        ];
        yield 'programs with some configured options' => [
            'programs' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_PAUSED,
                ProgramFixtures::PROGRAM_AGRICULTURE_ARCHIVED,
                ProgramFixtures::PROGRAM_CORPORATE_COMMERCIALIZED,
            ]),
            'fields' => $this->fieldRepository->findBy([
                'fieldAlias' => [
                    FieldAlias::AGRICULTURAL_BRANCH,
                    FieldAlias::AID_INTENSITY,
                    FieldAlias::FINANCING_OBJECT_TYPE,
                    FieldAlias::LEGAL_FORM,
                    FieldAlias::LOAN_NAF_CODE,
                    FieldAlias::TARGET_TYPE,
                ],
            ]),
        ];
    }
}
