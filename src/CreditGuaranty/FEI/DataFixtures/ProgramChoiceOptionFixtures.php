<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const INVESTMENT_THEMATIC_LIST = [
        'Renouvellement des générations et installation des nouveaux entrants dans le cadre '
            . 'd\'un projet agro-écologique ou d\'un projet générateur de valeur ajoutée et/ou d\'emplois',
        'Mieux répondre aux attentes des consommateurs / renforcer les filières de qualité, '
            . 'la contractualisation amont-aval et en encourageant '
            . 'l\'ancrage territorial et les circuits de proximité.',
        'Transformation des modèles agricoles pour une meilleure performance '
            . 'économique, sociale, environnementale et sanitaire et également à améliorer '
            . 'l\'innovation au sein des exploitations',
        'Accompagner la diversification des activités et des revenus des exploitations',
        'Mettre à niveau des actifs ne répondant plus aux meilleures pratiques internationales, '
            . 'afin de promouvoir l’utilisation efficace des ressources telles que l’énergie, la chaleur et l’eau',
    ];

    // user-defined list type fields
    private const FIELDS = [
        FieldAlias::ACTIVITY_COUNTRY => [
            'FR',
        ],
        FieldAlias::ACTIVITY_DEPARTMENT => [
            '75',
            '77',
            '94',
        ],
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
        FieldAlias::COMPANY_NAF_CODE => [
            '0111Z', '0121Z', '0141Z', '0142Z',
        ],
        FieldAlias::EXPLOITATION_SIZE => [
            '64', '512', '1024', '2048',
        ],
        FieldAlias::INVESTMENT_COUNTRY => [
            'FR',
        ],
        FieldAlias::INVESTMENT_DEPARTMENT => [
            '75',
            '77',
            '94',
        ],
        FieldAlias::INVESTMENT_LOCATION => [
            'Paris', 'Nantes', 'Lyon', 'Marseille', 'Nice',
        ],
        FieldAlias::INVESTMENT_THEMATIC => self::INVESTMENT_THEMATIC_LIST,
        FieldAlias::LEGAL_FORM          => [
            'SARL', 'SAS', 'SASU', 'EURL', 'SA', 'SELAS',
        ],
        FieldAlias::LOAN_NAF_CODE => [
            '0111Z', '0121Z', '0141Z', '0142Z',
        ],
        FieldAlias::LOAN_TYPE => [
            'term_loan', 'short_term', 'revolving_credit', 'stand_by', 'signature_commitment',
        ],
        FieldAlias::PRODUCT_CATEGORY_CODE => [
            '1 - ANIMAUX VIVANTS',
            '6 - PLANTES VIVANTES ET PRODUITS DE LA FLORICULTURE',
            '7 - LÉGUMES, PLANTES, RACINES ET TUBERCULES ALIMENTAIRES',
            '8 - FRUITS COMESTIBLES; ÉCORCES D’AGRUMES OU DE MELONS',
            '10 - CÉRÉALES',
            '401 - Lait et crème de lait, non concentrés ni additionnés de sucre ou d’autres édulcorants',
            '406 - Fromages et caillebotte',
            '8105000 - Kiwis, frais',
        ],
        FieldAlias::TARGET_TYPE => [
            'Cible A',
            'Cible B',
            'Cible C',
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
