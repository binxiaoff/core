<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Command;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\XLSX\Sheet;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Staff;
use KLS\Core\Repository\CompanyGroupTagRepository;
use KLS\Core\Repository\CompanyRepository;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramRepository;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportProgramReservationsCommand extends Command
{
    private const BATCH_SIZE = 50;

    private const MAPPING_KEYS = [
        "Adresse de l'entreprise"                     => FieldAlias::ACTIVITY_STREET,
        "Adresse de l'exploitation"                   => FieldAlias::ACTIVITY_STREET,
        'Apport'                                      => FieldAlias::PROJECT_CONTRIBUTION,
        'Branche agricole'                            => FieldAlias::AGRICULTURAL_BRANCH,
        "Chiffre d'affaire"                           => FieldAlias::TURNOVER,
        "Chiffre d'affaires"                          => FieldAlias::TURNOVER,
        "Chiffre d'affaires (en €)"                   => FieldAlias::TURNOVER,
        'Code CN'                                     => FieldAlias::PRODUCT_CATEGORY_CODE,
        'Code Postal'                                 => FieldAlias::ACTIVITY_POST_CODE,
        'Code postal'                                 => FieldAlias::ACTIVITY_POST_CODE,
        "Code postal de l'entreprise"                 => FieldAlias::ACTIVITY_POST_CODE,
        'Date de création'                            => FieldAlias::ACTIVITY_START_DATE,
        "Date de création de l'entreprise"            => FieldAlias::ACTIVITY_START_DATE,
        'Date de création de la demande'              => FieldAlias::RESERVATION_CREATION_DATE,
        'Date de signature'                           => FieldAlias::RESERVATION_SIGNING_DATE,
        'Date de signature du prêt'                   => FieldAlias::RESERVATION_SIGNING_DATE,
        'Date du premier déblocage du prêt'           => FieldAlias::FIRST_RELEASE_DATE,
        'Département'                                 => FieldAlias::ACTIVITY_DEPARTMENT,
        "Département de l'entreprise"                 => FieldAlias::ACTIVITY_DEPARTMENT,
        'Destination de financement'                  => FieldAlias::INVESTMENT_THEMATIC,
        "Thématique d'investissements"                => FieldAlias::INVESTMENT_THEMATIC,
        'Détails sur le projet'                       => FieldAlias::PROJECT_DETAIL,
        'Details sur le projet/Objet financé'         => FieldAlias::PROJECT_DETAIL,
        'Commentaire sur le projet'                   => FieldAlias::PROJECT_DETAIL,
        'dont BFR'                                    => FieldAlias::BFR_VALUE,
        'Dont BFR (en €)'                             => FieldAlias::BFR_VALUE,
        'Durée de la garantie'                        => FieldAlias::PROGRAM_DURATION,
        'Durée du prêt'                               => FieldAlias::LOAN_DURATION,
        'Durée du prêt (en mois)'                     => FieldAlias::LOAN_DURATION,
        "Effectif de l'exploitation"                  => FieldAlias::EMPLOYEES_NUMBER,
        "Nombre d'employés"                           => FieldAlias::EMPLOYEES_NUMBER,
        'Garantie complémentaire'                     => FieldAlias::ADDITIONAL_GUARANTY,
        'Garanties complémentaires'                   => FieldAlias::ADDITIONAL_GUARANTY,
        'Grade Balôis'                                => FieldAlias::BORROWER_TYPE_GRADE,
        'Grade Bâlois'                                => FieldAlias::BORROWER_TYPE_GRADE,
        "Intensité d'aides"                           => FieldAlias::AID_INTENSITY,
        'Jeune Agriculteur'                           => FieldAlias::YOUNG_FARMER,
        'Jeune Agriculteur ?'                         => FieldAlias::YOUNG_FARMER,
        "L'emprunteur fait partie d'un groupe?"       => FieldAlias::SUBSIDIARY,
        "L'emprunteur fait-il partie d'un groupe ?"   => FieldAlias::SUBSIDIARY,
        "Localisation de l'investissement"            => FieldAlias::INVESTMENT_LOCATION,
        'Montant du crédit FEI (partie corporelle)'   => FieldAlias::TANGIBLE_FEI_CREDIT,
        'Montant du crédit FEI (partie incorporelle)' => FieldAlias::INTANGIBLE_FEI_CREDIT,
        'Montant du crédit hors FEI'                  => FieldAlias::CREDIT_EXCLUDING_FEI,
        'Montant total du crédit FEI'                 => FieldAlias::TOTAL_FEI_CREDIT,
        'Montant total du crédit FEI (en €)'          => FieldAlias::TOTAL_FEI_CREDIT,
        'Montant total du projet client'              => FieldAlias::PROJECT_TOTAL_AMOUNT,
        'Montant total du projet Client'              => FieldAlias::PROJECT_TOTAL_AMOUNT,
        'Montant total du projet client (en €)'       => FieldAlias::PROJECT_TOTAL_AMOUNT,
        'Montant total éligible FEI'                  => FieldAlias::ELIGIBLE_FEI_CREDIT,
        'N° d’immatriculation'                        => FieldAlias::REGISTRATION_NUMBER,
        "NAF de l'exploitation"                       => FieldAlias::COMPANY_NAF_CODE,
        'Libellé code NAF'                            => FieldAlias::COMPANY_NAF_CODE,
        "Nom de l'agriculteur"                        => FieldAlias::BENEFICIARY_NAME,
        "Nom de l'entreprise"                         => FieldAlias::BENEFICIARY_NAME,
        'Numéro du prêt'                              => FieldAlias::LOAN_NUMBER,
        'Objet du financement'                        => FieldAlias::FINANCING_OBJECT_NAME,
        'Période du différé'                          => FieldAlias::LOAN_DEFERRAL,
        'Période du différé (en mois)'                => FieldAlias::LOAN_DEFERRAL,
        'Périodicité du prêt'                         => FieldAlias::LOAN_PERIODICITY,
        'Renouvellement des générations ?'            => FieldAlias::SUPPORTING_GENERATIONS_RENEWAL,
        'Statut du prêt'                              => FieldAlias::RESERVATION_STATUS,
        'Subventions liées au projet'                 => FieldAlias::PROJECT_GRANT,
        "Taille de l'exploitation (en hectare)"       => FieldAlias::EXPLOITATION_SIZE,
        'Total bilan'                                 => FieldAlias::TOTAL_ASSETS,
        'Total bilan (en €)'                          => FieldAlias::TOTAL_ASSETS,
        "Type d'investissement"                       => FieldAlias::INVESTMENT_TYPE,
        'Nature du financement (codification FEI)'    => FieldAlias::INVESTMENT_TYPE,
        'Type de cible'                               => FieldAlias::TARGET_TYPE,
        'Type de prêt'                                => FieldAlias::LOAN_TYPE,
        'Ville'                                       => FieldAlias::ACTIVITY_CITY,
        "Ville de l'entreprise"                       => FieldAlias::ACTIVITY_CITY,
    ];

    private const MAPPING_COMPANY_SHORTCODES = [
        'Alpes Provence'                => 'CAPR',
        'Alpes-Provence'                => 'CAPR',
        'Alsace Vosges'                 => 'ALVO',
        'Alsace-Vosges'                 => 'ALVO',
        'Anjou et Maine'                => 'ANMA',
        'Anjou-et-Maine'                => 'ANMA',
        'Anjou&Maine'                   => 'ANMA',
        'Aquitaine'                     => 'AQTN',
        'Atlantique Vendée'             => 'ATVD',
        'Atlantique-Vendée'             => 'ATVD',
        'Brie Picardie'                 => 'BRPI',
        'Brie-Picardie'                 => 'BRPI',
        'Centre Est'                    => 'CEST',
        'Centre-Est'                    => 'CEST',
        'Centre France'                 => 'CENF',
        'Centre-France'                 => 'CENF',
        'Centre Loire'                  => 'CENL',
        'Centre-Loire'                  => 'CENL',
        'Centre Ouest'                  => 'COUE',
        'Centre-Ouest'                  => 'COUE',
        'Champagne Bourgogne'           => 'CHBO',
        'Champagne-Bourgogne'           => 'CHBO',
        'Charente Maritime Deux-Sèvres' => 'CM2SE',
        'Charente-Maritime-Deux-Sèvres' => 'CM2SE',
        'Charente Périgord'             => 'CHPE',
        'Charente-Périgord'             => 'CHPE',
        'Corse'                         => 'CORS',
        "Côtes d'Armor"                 => 'CODA',
        'des Savoie'                    => 'SAVO',
        'Des Savoie'                    => 'SAVO',
        'Finistère'                     => 'FINI',
        'Franche Comté'                 => 'FRAC',
        'Franche-Comté'                 => 'FRAC',
        'Guadeloupe'                    => 'GUAD',
        'Ille-et-Vilaine'               => 'ILVI',
        'Ille-et-vilaine'               => 'ILVI',
        'Languedoc'                     => 'LANG',
        'Loire Haute-Loire'             => 'L&HL',
        'Loire-Haute-Loire'             => 'L&HL',
        'Lorraine'                      => 'LORR',
        'Martinique-Guyane'             => 'MART',
        'Morbihan'                      => 'MORB',
        'Nord de France'                => 'NORF',
        'Nord-de-France'                => 'NORF',
        'Nord Est'                      => 'NEST',
        'Nord-Est'                      => 'NEST',
        'Nord Midi Pyrénées'            => 'NMPY',
        'Nord-Midi-Pyrénées'            => 'NMPY',
        'Normandie'                     => 'NORM',
        'Normandie Seine'               => 'NORS',
        'Normandie-Seine'               => 'NORS',
        'Paris et Ile-de-France'        => 'IDFR',
        "Provence Côte d'Azur"          => 'PRCA',
        "Provence-Côte d'Azur"          => 'PRCA',
        'Pyrénées Gascogne'             => 'PYGA',
        'Pyrénées-Gascogne'             => 'PYGA',
        'La Réunion'                    => 'REUN',
        'Sud Rhône Alpes'               => 'SRAL',
        'Sud-Rhône-Alpes'               => 'SRAL',
        'Sud Méditerranée'              => 'SMED',
        'Sud-Méditerranée'              => 'SMED',
        'Toulouse 31'                   => 'TOUL',
        'Toulouse-31'                   => 'TOUL',
        'Toulouse31'                    => 'TOUL',
        'Touraine Poitou'               => 'TPOI',
        'Touraine-Poitou'               => 'TPOI',
        'Val de France'                 => 'VALF',
        'Val-de-France'                 => 'VALF',
        'LCL'                           => 'LCL',
        'CA-CIB'                        => 'CIB',
        'Unifergie'                     => 'CALF',
    ];

    private const MAPPING_LOAN_PERIODICITIES = [
        'Mensuelle'     => 'monthly',
        'Trimestrielle' => 'quarterly',
        'Semestrielle'  => 'semi_annually',
        'Annuelle'      => 'annually',
    ];

    private const MAPPING_RESERVATION_STATUSES = [
        'Brouillon' => [
            'value'            => ReservationStatus::STATUS_DRAFT,
            'previousStatuses' => [],
        ],
        'Réservé' => [
            'value'            => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
            'previousStatuses' => [
                ReservationStatus::STATUS_SENT,
            ],
        ],
        'Contractualisé' => [
            'value'            => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
            'previousStatuses' => [
                ReservationStatus::STATUS_SENT,
                ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
            ],
        ],
    ];

    private const ESB_CALCULATION_ACTIVATED_KEYS = [
        'Montant Equivalent Subvention Brut',
        'Montant Équivalent Subvention Brut',
    ];

    protected static $defaultName = 'kls:fei:program:reservations:import';

    private CompanyRepository $companyRepository;
    private CompanyGroupTagRepository $companyGroupTagRepository;
    private FieldRepository $fieldRepository;
    private ProgramRepository $programRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ReservationRepository $reservationRepository;
    private FinancingObjectRepository $financingObjectRepository;
    private ReservationAccessor $reservationAccessor;

    public function __construct(
        CompanyRepository $companyRepository,
        CompanyGroupTagRepository $companyGroupTagRepository,
        FieldRepository $fieldRepository,
        ProgramRepository $programRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ReservationRepository $reservationRepository,
        FinancingObjectRepository $financingObjectRepository,
        ReservationAccessor $reservationAccessor
    ) {
        parent::__construct();
        $this->companyRepository             = $companyRepository;
        $this->companyGroupTagRepository     = $companyGroupTagRepository;
        $this->fieldRepository               = $fieldRepository;
        $this->programRepository             = $programRepository;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->reservationRepository         = $reservationRepository;
        $this->financingObjectRepository     = $financingObjectRepository;
        $this->reservationAccessor           = $reservationAccessor;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Imports program reservations from Excel file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Absolute path to the folder containing data xlsx to import'
            )
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = $input->getArgument('path');

        $io->title(\sprintf('Start program reservations import from "%s" file', $path));

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);
        $sheetIterator = $reader->getSheetIterator();
        $sheetIterator->rewind();
        $sheets      = \iterator_to_array($sheetIterator, false);
        $sheetsCount = \count($sheets);

        if (0 === \count($sheets)) {
            throw new Exception('Sheets are not found in the file');
        }

        $fileNameParts = \explode('/', $path);
        $fileName      = \trim(\pathinfo(\end($fileNameParts), PATHINFO_FILENAME));

        // program
        $program = $this->createProgram($fileName);
        $this->programRepository->save($program);

        $reservationCount = 0;

        /** @var Sheet $sheet */
        foreach ($sheets as $key => $sheet) {
            $io->info(\sprintf('%s/%s Importing "%s" sheet data...', $key + 1, $sheetsCount, $sheet->getName()));

            $keys   = [];
            $fields = [];
            $i      = 0;

            // reservations
            foreach ($sheet->getRowIterator() as $rowKey => $row) {
                if (1 === $rowKey) {
                    // put header name as key to convert them to fieldAlias
                    $keys = \array_flip($row->toArray());
                    $this->replaceKeys($keys);
                    // put row index as key with its related fieldAlias to easy get cell value
                    $keys = \array_flip($keys);

                    $fields = $this->fieldRepository->findBy(['fieldAlias' => $keys]);

                    continue;
                }

                $data = $row->toArray();
                $this->replaceKeys($data, $keys);

                $reservation = $this->createReservation($program, $fields, $data);
                $this->reservationRepository->persist($reservation);
                ++$reservationCount;
                ++$i;

                if (0 === $i % self::BATCH_SIZE) {
                    $this->reservationRepository->flush();
                }
            }

            $this->reservationRepository->flush();
        }

        $this->formatDuplicatedReservationNames($program);

        $io->success(
            \sprintf(
                '%s reservations have been created for the program "%s"',
                $reservationCount,
                $program->getName()
            )
        );

        return Command::SUCCESS;
    }

    private function replaceKeys(array &$data, ?array $keys = null): void
    {
        $keysToCheck = $keys ?? self::MAPPING_KEYS;

        foreach ($data as $key => $value) {
            if (false === isset($keysToCheck[$key])) {
                continue;
            }

            $data[$keysToCheck[$key]] = $value;
            unset($data[$key]);
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function createProgram(string $name): Program
    {
        // prefix program name if already exists in db
        $programCount = $this->programRepository->countByPartialName($name);
        if ($programCount > 0) {
            $name .= ' (' . ($programCount + 1) . ')';
        }

        /** @var CompanyGroupTag $companyGroupTag */
        $companyGroupTag = $this->companyGroupTagRepository->findOneBy([
            'code' => Program::COMPANY_GROUP_TAG_AGRICULTURE,
        ]);
        /** @var Company $casaCompany */
        $casaCompany = $this->companyRepository->findOneBy(['shortCode' => Company::SHORT_CODE_CASA]);
        $casaStaff   = $casaCompany->getStaffCount() > 0
            ? $this->getManagerStaff($casaCompany)
            : $casaCompany->getStaff()[0];

        $program = new Program($name, $companyGroupTag, new Money('EUR', '100000000'), $casaStaff);
        $program
            ->setCurrentStatus(new ProgramStatus($program, 20, $casaStaff))
            ->setCurrentStatus(new ProgramStatus($program, 30, $casaStaff))
            ->setRatingType(CARatingType::CA_INTERNAL_RETAIL_RATING)
        ;

        return $program;
    }

    /**
     * @param array|Field[] $fields
     *
     * @throws ReflectionException
     * @throws ORMException
     */
    private function createReservation(Program $program, array $fields, array $data): Reservation
    {
        /** @var Company $crCompany */
        $crCompany = $this->companyRepository->findOneBy([
            'shortCode' => self::MAPPING_COMPANY_SHORTCODES[$data['Libellé CR']],
        ]);
        $crStaff = $this->getManagerStaff($crCompany) ?? \iterator_to_array($crCompany->getStaff())[0];

        $reservation = new Reservation($program, $crStaff);
        $reservation->setName(\trim($data[FieldAlias::BENEFICIARY_NAME]));
        $this->reservationRepository->persist($reservation);

        foreach ($fields as $field) {
            $object = $this->getObjectByField($reservation, $field, $data);

            if (null !== $object) {
                $propertyPath = ('reservation' === $field->getCategory())
                    ? $field->getReservationPropertyName()
                    : $field->getPropertyPath();

                $value = $this->formatValue($reservation, $field, $data[$field->getFieldAlias()]);

                if (FieldAlias::RESERVATION_STATUS === $field->getFieldAlias()) {
                    $statusData = self::MAPPING_RESERVATION_STATUSES[$value];
                    $value      = new ReservationStatus($reservation, $statusData['value'], $crStaff);

                    foreach ($statusData['previousStatuses'] as $previousStatus) {
                        $reservation->setCurrentStatus(new ReservationStatus($reservation, $previousStatus, $crStaff));
                    }
                }

                if (null !== $value) {
                    $this->forcePropertyValue($object, $propertyPath, $value);

                    if (FieldAlias::BENEFICIARY_NAME === $field->getFieldAlias()) {
                        $object->setCompanyName($value);
                    }
                }
            }
        }

        if (null === $program->isEsbCalculationActivated()) {
            foreach (self::ESB_CALCULATION_ACTIVATED_KEYS as $key) {
                if (\array_key_exists($key, $data)) {
                    $program->setEsbCalculationActivated(true);

                    break;
                }
            }
        }

        return $reservation;
    }

    private function getManagerStaff(Company $company): ?Staff
    {
        foreach ($company->getStaff() as $staff) {
            if ($staff->isActive() && $staff->isManager()) {
                return $staff;
            }
        }

        return null;
    }

    /**
     * @throws ORMException
     */
    private function getObjectByField(Reservation $reservation, Field $field, array $data): ?object
    {
        switch ($field->getCategory()) {
            case 'program':
            case 'profile':
            case 'project':
                return $this->reservationAccessor->getEntity($reservation, $field);

            case 'reservation':
                return $reservation;

            case 'loan':
                $object = $reservation->getFinancingObjects()->first();

                if (false === $object) {
                    $loanMoneyValue = false === empty($data[FieldAlias::PROJECT_TOTAL_AMOUNT])
                        ? $data[FieldAlias::PROJECT_TOTAL_AMOUNT]
                        : '0';
                    $object = new FinancingObject(
                        $reservation,
                        new Money('EUR', (string) $loanMoneyValue),
                        true,
                        $data[FieldAlias::FINANCING_OBJECT_NAME]
                    );
                    $this->financingObjectRepository->persist($object);
                    $reservation->addFinancingObject($object);
                }

                return $object;
        }

        return null;
    }

    /**
     * @param mixed $value
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function getProgramChoiceOption(Program $program, Field $field, $value): ProgramChoiceOption
    {
        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field,
            'description' => (string) $value,
        ]);

        if (false === ($programChoiceOption instanceof ProgramChoiceOption)) {
            $programChoiceOption = new ProgramChoiceOption($program, (string) $value, $field);
            $this->programChoiceOptionRepository->save($programChoiceOption);
        }

        return $programChoiceOption;
    }

    /**
     * @param mixed $value
     *
     * @throws ORMException
     * @throws Exception
     *
     * @return mixed
     */
    private function formatValue(Reservation $reservation, Field $field, $value)
    {
        if (FieldAlias::RESERVATION_STATUS === $field->getFieldAlias()) {
            return $value;
        }

        if (false !== $value && empty($value)) {
            if ('Money' === $field->getPropertyType()) {
                return new Money('EUR', '0');
            }

            return null;
        }

        switch ($field->getPropertyType()) {
            case 'string':
                return \trim((string) $value);

            case 'int':
                return (int) $value;

            case 'bool':
                return (bool) $value;

            case 'DateTimeImmutable':
                return DateTimeImmutable::createFromMutable($value);

            case 'MoneyInterface':
            case 'NullableMoney':
                return new NullableMoney('EUR', (string) $value);

            case 'Money':
                return new Money('EUR', (string) $value);

            case 'ProgramChoiceOption':
                $fieldAlias = $field->getFieldAlias();

                if (FieldAlias::LOAN_PERIODICITY === $fieldAlias) {
                    $value = self::MAPPING_LOAN_PERIODICITIES[$value];
                }
                if (\array_key_exists($fieldAlias, FieldAlias::NAF_NACE_FIELDS)) {
                    // keep only naf code without its title
                    $value = \preg_replace('/\s-\s.+$/', '', $value);
                }
                if ('percentage' === $field->getUnit()) {
                    $value = (float) $value / 100;
                }

                return $this->getProgramChoiceOption($reservation->getProgram(), $field, $value);

            case 'Collection':
                $programChoiceOption = $this->getProgramChoiceOption($reservation->getProgram(), $field, $value);

                $object = $this->getObjectByField($reservation, $field, []);
                /** @var Collection $value */
                $value = $this->reservationAccessor->getValue($object, $field);
                $value->add($programChoiceOption);

                return $value;
        }

        return $value;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function formatDuplicatedReservationNames(Program $program): void
    {
        $duplicatedReservations = $this->reservationRepository->findIdsByDuplicatedName($program);

        $i = 0;

        foreach ($duplicatedReservations as $ids) {
            // we remove the first element because we do no need to rename the first duplicated reservation
            \array_shift($ids);
            $reservations = $this->reservationRepository->findBy(['id' => $ids]);

            foreach ($reservations as $key => $reservation) {
                $reservation->setName(\sprintf('%s %s', $reservation->getName(), $key + 2));
                $this->reservationRepository->persist($reservation);
                ++$i;

                if (0 === $i % self::BATCH_SIZE) {
                    $this->reservationRepository->flush();
                }
            }
        }

        $this->reservationRepository->flush();
    }

    /**
     * @param mixed $value
     *
     * @throws ReflectionException
     */
    private function forcePropertyValue(object $object, string $property, $value): void
    {
        $ref               = new ReflectionClass(\get_class($object));
        $reflexionProperty = $ref->getProperty($property);
        $reflexionProperty->setAccessible(true);
        $reflexionProperty->setValue($object, $value);
    }
}
