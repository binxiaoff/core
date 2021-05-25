<?php

declare(strict_types=1);

namespace Unilend\Agency\Command;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\SharedStringNotFoundException;
use Box\Spout\Reader\XLSX\Sheet;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Iterator;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Unilend\Agency\Entity\Borrower;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\BorrowerTrancheShare;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\CovenantRule;
use Unilend\Agency\Entity\Embeddable\Inequality;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationTrancheAllocation;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Tranche;
use Unilend\Agency\Repository\ProjectRepository;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\FundingSpecificity;
use Unilend\Core\Entity\Constant\LegalForm;
use Unilend\Core\Entity\Constant\MathOperator;
use Unilend\Core\Entity\Constant\Tranche\CommissionType;
use Unilend\Core\Entity\Constant\Tranche\LoanType;
use Unilend\Core\Entity\Constant\Tranche\RepaymentType;
use Unilend\Core\Entity\Embeddable\LendingRate;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Embeddable\NullablePerson;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Model\Bitmask;
use Unilend\Core\Repository\CompanyGroupTagRepository;
use Unilend\Core\Repository\CompanyRepository;
use Unilend\Core\Repository\StaffRepository;
use Unilend\Core\Repository\UserRepository;

class ImportProjectCommand extends Command
{
    private const SHEET_INDEX_PROJECT      = 0;
    private const SHEET_INDEX_BORROWERS    = 1;
    private const SHEET_INDEX_PARTICIPANTS = 2;
    private const SHEET_INDEX_TRANCHES     = 3;
    private const SHEET_INDEX_COVENANTS    = 4;

    private const MAPPING_COMPANY_GROUP_TAG = [
        'Promotion immobilière'    => 'real_estate_development',
        'Collectivités publiques'  => 'public_collectivity',
        'Patrimonial'              => 'patrimonial',
        'Partenariat Public Privé' => 'ppp',
        'Énergies renouvelables'   => 'energy',
        'Entreprise'               => 'corporate',
        'Agriculture'              => 'agriculture',
        'Pro'                      => 'pro',
    ];

    private const MAPPING_FUNDING_SPECIFICITY = [
        'Aucune' => null,
        'FSA'    => FundingSpecificity::FSA,
        'LBO'    => FundingSpecificity::LBO,
    ];

    private const MAPPING_LEGAL_FORM = [
        'SARL'           => LegalForm::SARL,
        'SAS'            => LegalForm::SAS,
        'SASU'           => LegalForm::SASU,
        'EURL'           => LegalForm::EURL,
        'SA'             => LegalForm::SA,
        'SELAS'          => LegalForm::SELAS,
        'Société Civile' => LegalForm::MANAGEMENT_COMPANY,
        'SCEA'           => LegalForm::SCEA,
    ];

    private const MAPPING_RATE_TYPE = [
        'Fixe'    => LendingRate::INDEX_FIXED,
        'E1M'     => LendingRate::INDEX_EURIBOR_1_MONTH,
        'E3M'     => LendingRate::INDEX_EURIBOR_3_MONTHS,
        'E6M'     => LendingRate::INDEX_EURIBOR_6_MONTHS,
        'E12M'    => LendingRate::INDEX_EURIBOR_12_MONTHS,
        'EONIA'   => LendingRate::INDEX_EONIA,
        'SONIA'   => LendingRate::INDEX_SONIA,
        'LIBOR'   => LendingRate::INDEX_LIBOR,
        'CHFTOIS' => LendingRate::INDEX_CHFTOIS,
        'FFER'    => LendingRate::INDEX_FFER,
        '€STR'    => LendingRate::INDEX_ESTER,
    ];

    private const MAPPING_RATE_FLOOR_TYPE = [
        'Auncun'      => LendingRate::FLOOR_TYPE_NONE,
        'Index'       => LendingRate::FLOOR_TYPE_INDEX,
        'Index+Marge' => LendingRate::FLOOR_TYPE_INDEX_RATE,
    ];

    private const MAPPING_LOAN_TYPE = [
        'Term loan'                => LoanType::TERM_LOAN,
        'RCF'                      => LoanType::REVOLVING_CREDIT,
        'Court terme'              => LoanType::SHORT_TERM,
        'Stand by'                 => LoanType::STAND_BY,
        'Engagement par signature' => LoanType::SIGNATURE_COMMITMENT,
    ];

    private const MAPPING_REPAYMENT_TYPE = [
        'Capital constant'   => RepaymentType::CONSTANT_CAPITAL,
        'Échéance constante' => RepaymentType::FIXED,
        'In fine'            => RepaymentType::IN_FINE,
        'Atypique'           => RepaymentType::ATYPICAL,
    ];

    private const MAPPING_COMMISSION_TYPE = [
        'Aucune'          => null,
        'Non utilisation' => CommissionType::NON_UTILISATION,
        'Engagement'      => CommissionType::COMMITMENT,
    ];

    private const MAPPING_COVENANT_NATURE = [
        'Autre - Contrôle à effectuer' => Covenant::NATURE_CONTROL,
        'Autre - Document à fournir'   => Covenant::NATURE_DOCUMENT,
        'Elément Financier'            => Covenant::NATURE_FINANCIAL_ELEMENT,
        'Ratio Financier'              => Covenant::NATURE_FINANCIAL_RATIO,
    ];

    private const MAPPING_COVENANT_RECURRENCE = [
        'Mensuelle'     => Covenant::RECURRENCE_1M,
        'Trimestrielle' => Covenant::RECURRENCE_3M,
        'Semestrielle'  => Covenant::RECURRENCE_6M,
        'Annuelle'      => Covenant::RECURRENCE_12M,
    ];

    private const MAPPING_COVENANT_RULE_OPERATOR = [
        '>'  => MathOperator::SUPERIOR,
        '>=' => MathOperator::SUPERIOR_OR_EQUAL,
        '<'  => MathOperator::INFERIOR,
        '<=' => MathOperator::INFERIOR_OR_EQUAL,
        '='  => MathOperator::EQUAL,
    ];

    protected static $defaultName = 'kls:agency:import';

    private ArrayCollection $tranches;

    private FilesystemInterface $userAttachmentFilesystem;

    private ProjectRepository $projectRepository;

    private CompanyRepository $companyRepository;

    private StaffRepository $staffRepository;

    private CompanyGroupTagRepository $companyGroupTagRepository;

    private UserRepository $userRepository;

    private TokenStorageInterface $tokenStorage;

    private Iterator $sheetIterator;

    private Project $project;

    private SymfonyStyle $io;

    public function __construct(
        FilesystemInterface $userAttachmentFilesystem,
        ProjectRepository $projectRepository,
        CompanyRepository $companyRepository,
        StaffRepository $staffRepository,
        CompanyGroupTagRepository $companyGroupTagRepository,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct();

        // Set all tranches at once instead of adding tranches one by one in order to be able to use collection index when setting participant allocation
        $this->tranches                  = new ArrayCollection();
        $this->userAttachmentFilesystem  = $userAttachmentFilesystem;
        $this->projectRepository         = $projectRepository;
        $this->companyRepository         = $companyRepository;
        $this->staffRepository           = $staffRepository;
        $this->companyGroupTagRepository = $companyGroupTagRepository;
        $this->userRepository            = $userRepository;
        $this->tokenStorage              = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('This command imports agency projects from Excel files.');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run command to check import file data');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);
        $dryRun   = $input->getOption('dry-run');

        /**
         * File hierarchy should look like this under storage/protected/import.
         *
         * +-- pending
         * |   +-- 10. Bank 10 name
         * |   |   +-- file1.xlsx
         * |   |   +-- file2.xlsx
         * |   +-- 12. Bank 12 name
         * |   |   +-- file1.xlsx
         * |   |   +-- file2.xlsx
         * +-- processed
         * |   +-- 1. Bank 1 name
         * |   |   +-- file1.xlsx
         * |   |   +-- file2.xlsx
         * |   +-- 5. Bank 5 name
         * |   |   +-- file1.xlsx
         * |   |   +-- file2.xlsx
         * |   |   +-- file3.xlsx
         */
        // @todo Get files (.xlsx) from Flysystem
        // @todo Parent directory corresponds to company ID then import all files from directory
        $reader = ReaderEntityFactory::createReaderFromFile('deuport.xlsx');
        $reader->open('/var/www/var/storage/protected/attachment/deuport.xlsx');

        // @todo ID should be parent directory name
        $company = $this->companyRepository->find(1);

        if (null === $company) {
            $this->io->error(sprintf('The company with ID "%s" was not found. Cannot start import.', $input->getArgument('company')));

            return Command::FAILURE;
        }

        $this->io->writeln(sprintf('Importing files for %s...', $company->getDisplayName()));
        // @todo
        $this->io->writeln(sprintf('Importing file %s...', '@todo file name'));

        $this->sheetIterator = $reader->getSheetIterator();

        $importStatus = $this->importGeneralInformation($company);
        if (Command::SUCCESS !== $importStatus) {
            return $importStatus;
        }

        $importStatus = $this->importBorrowers();
        if (Command::SUCCESS !== $importStatus) {
            return $importStatus;
        }

        $importStatus = $this->importTranches();
        if (Command::SUCCESS !== $importStatus) {
            return $importStatus;
        }

        $importStatus = $this->importParticipants();
        if (Command::SUCCESS !== $importStatus) {
            return $importStatus;
        }

        $importStatus = $this->importCovenants();
        if (Command::SUCCESS !== $importStatus) {
            return $importStatus;
        }

        if (false === $dryRun) {
            $this->projectRepository->save($this->project);

            // @todo Move processed files to "processed" directory
        }

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     * @throws IOException
     * @throws SharedStringNotFoundException
     * @throws NonUniqueResultException
     */
    private function importGeneralInformation(Company $company): int
    {
        $this->io->writeln('Importing general information...');

        $sheet       = $this->getSheet(self::SHEET_INDEX_PROJECT);
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();
        $rowIterator->next();

        $row   = $rowIterator->current();
        $cells = $row->getCells();

        $internalRating = trim($cells[3]->getValue());

        if (false === in_array($internalRating, CAInternalRating::getConstList(), true)) {
            $this->io->error(sprintf('Invalid internal rating "%s".', $cells[3]->getValue()));

            return Command::FAILURE;
        }

        $companyGroupTag = $this->getMapping($cells[6]->getValue(), self::MAPPING_COMPANY_GROUP_TAG);
        $companyGroupTag = $this->companyGroupTagRepository->findOneBy(['code' => $companyGroupTag, 'companyGroup' => $company->getCompanyGroup()]);

        if (null === $companyGroupTag) {
            $this->io->error(sprintf('Invalid market "%s".', $cells[6]->getValue()));
        }

        $fundingSpecificity = $this->getMapping($cells[7]->getValue(), self::MAPPING_FUNDING_SPECIFICITY);

        if (null !== $fundingSpecificity && false === in_array($fundingSpecificity, FundingSpecificity::getConstList(), true)) {
            $this->io->error(sprintf('Invalid funding specificity "%s".', $cells[7]->getValue()));

            return Command::FAILURE;
        }

        $contactEmail = trim($cells[13]->getValue());

        if (false === filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $this->io->error(sprintf('Contact email "%s" is not a correct value.', $cells[13]->getValue()));

            return Command::FAILURE;
        }

        $staff = $this->staffRepository->findOneByEmailAndCompany($contactEmail, $company);

        if (null === $staff) {
            $user = $this->userRepository->findOneBy(['email' => $contactEmail]);

            if (null === $user) {
                $user = new User($contactEmail);
            }

            $staff = new Staff($user, $company->getRootTeam());
        }

        $user  = $staff->getUser();
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'api');
        $token->setAttribute('staff', $staff);
        $token->setAttribute('company', $staff->getCompany());

        $this->tokenStorage->setToken($token);

        $closingDate        = DateTimeImmutable::createFromMutable($cells[0]->getValue());
        $contractEndDate    = DateTimeImmutable::createFromMutable($cells[1]->getValue());
        $riskGroupName      = trim($cells[2]->getValue());
        $title              = trim($cells[4]->getValue());
        $globalFundingMoney = new Money('EUR', (string) $cells[5]->getValue());
        $description        = trim($cells[8]->getValue());
        $contactLastName    = trim($cells[9]->getValue());
        $contactFirstName   = trim($cells[10]->getValue());
        $contactOccupation  = trim($cells[11]->getValue());
        $contactPhone       = trim($cells[12]->getValue());

        $this->project = (new Project($staff, $title, $riskGroupName, $globalFundingMoney, $closingDate, $contractEndDate))
            ->setInternalRatingScore($internalRating)
            ->setCompanyGroupTag($companyGroupTag)
            ->setFundingSpecificity($fundingSpecificity)
            ->setDescription($description)
        ;

        $this->project
            ->getAgent()
            ->setContact(
                (new NullablePerson())
                    ->setLastName($contactLastName)
                    ->setFirstName($contactFirstName)
                    ->setOccupation($contactOccupation)
                    ->setPhone($contactPhone)
                    ->setEmail($contactEmail)
            )
        ;

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function importBorrowers(): int
    {
        $this->io->writeln('Importing borrowers...');

        $sheet       = $this->getSheet(self::SHEET_INDEX_BORROWERS);
        $rowIterator = $sheet->getRowIterator();

        $borrowers = new ArrayCollection();
        foreach ($rowIterator as $row) {
            if (1 === $rowIterator->key()) {
                continue;
            }

            $cells = $row->getCells();

            $siren = preg_replace('/\D*/', '', $cells[1]->getValue());

            if (1 !== preg_match('/^\d{9}$/', $siren)) {
                $this->io->error(sprintf('Invalid SIREN "%s".', $cells[1]->getValue()));

                return Command::FAILURE;
            }

            $legalForm = $this->getMapping($cells[4]->getValue(), self::MAPPING_LEGAL_FORM);

            if (null === $legalForm) {
                $this->io->error(sprintf('Invalid legal form "%s".', $cells[4]->getValue()));

                return Command::FAILURE;
            }

            $name    = trim($cells[0]->getValue());
            $address = trim($cells[2]->getValue());
            $rcs     = trim($cells[3]->getValue());
            $capital = preg_replace('/\D*/', '', $cells[5]->getValue());
            $capital = new NullableMoney('EUR', $capital);

            $borrower = (new Borrower(
                $this->project,
                $this->tokenStorage->getToken()->getAttribute('staff'),
                $name,
                $legalForm,
                $capital,
                $address,
                $siren
            ))->setRcs($rcs);

            $signatoryEmail = trim($cells[9]->getValue());

            if (false === empty($signatoryEmail)) {
                if (false === filter_var($signatoryEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->io->error(sprintf('Invalid signatory email "%s".', $cells[9]->getValue()));

                    return Command::FAILURE;
                }

                $signatoryUser = $this->userRepository->findOneBy(['email' => $signatoryEmail]);

                if (null === $signatoryUser) {
                    $signatoryUser = (new User($signatoryEmail))
                        ->setLastName(trim($cells[6]->getValue()))
                        ->setFirstName(trim($cells[7]->getValue()))
                    ;
                }

                $signatory = (new BorrowerMember($borrower, $signatoryUser))
                    ->setProjectFunction(trim($cells[8]->getValue()))
                ;
                $borrower->setSignatory($signatory);
            }

            $referentEmail = trim($cells[13]->getValue());

            if (false === empty($referentEmail)) {
                if (false === filter_var($referentEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->io->error(sprintf('Invalid referent email "%s".', $cells[13]->getValue()));

                    return Command::FAILURE;
                }

                $referentUser = $this->userRepository->findOneBy(['email' => $referentEmail]);

                if (null === $referentUser) {
                    $referentUser = (new User($referentEmail))
                        ->setLastName(trim($cells[10]->getValue()))
                        ->setFirstName(trim($cells[11]->getValue()))
                    ;
                }

                $referent = (new BorrowerMember($borrower, $referentUser))->setProjectFunction(trim($cells[12]->getValue()));
                $borrower->setReferent($referent);
            }

            $borrowers->add($borrower);
        }

        $this->project->setBorrowers($borrowers);

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function importTranches(): int
    {
        $this->io->writeln('Importing tranches...');

        $sheet       = $this->getSheet(self::SHEET_INDEX_TRANCHES);
        $rowIterator = $sheet->getRowIterator();

        foreach ($rowIterator as $row) {
            if (1 === $rowIterator->key()) {
                continue;
            }

            $cells = $row->getCells();

            $rateType = $this->getMapping($cells[6]->getValue(), self::MAPPING_RATE_TYPE);

            if (null === $rateType) {
                $this->io->error(sprintf('Unknown rate type "%s".', $rateType));

                return Command::FAILURE;
            }

            $index          = (int) $cells[0]->getValue();
            $name           = trim($cells[1]->getValue());
            $syndicated     = 'Oui' === trim($cells[2]->getValue());
            $money          = new Money('EUR', (string) $cells[3]->getValue());
            $validityDate   = DateTimeImmutable::createFromMutable($cells[4]->getValue());
            $duration       = (int) $cells[5]->getValue();
            $loanType       = $this->getMapping($cells[10]->getValue(), self::MAPPING_LOAN_TYPE);
            $repaymentType  = $this->getMapping($cells[11]->getValue(), self::MAPPING_REPAYMENT_TYPE);
            $comment        = trim($cells[14]->getValue());
            $rateMargin     = (string) $cells[7]->getValue();
            $rateFloorType  = LendingRate::INDEX_FIXED === $rateType ? null : $this->getMapping($cells[8]->getValue(), self::MAPPING_RATE_FLOOR_TYPE);
            $rateFloorValue = $rateFloorType ? (string) $cells[9]->getValue() : null;
            $trancheRate    = new LendingRate($rateType, $rateMargin, $rateFloorValue, $rateFloorType);

            // @todo Color?
            $tranche = (new Tranche($this->project, $name, $syndicated, '#fff', $loanType, $repaymentType, $duration, $money, $trancheRate))
                ->setValidityDate($validityDate)
                ->setComment($comment)
            ;

            $commissionType = $this->getMapping($cells[12]->getValue(), self::MAPPING_COMMISSION_TYPE);
            $commissionRate = (string) $cells[13]->getValue();

            if (false === empty($commissionType)) {
                if (empty($commissionRate)) {
                    $this->io->error(sprintf('Tranche #%s commission rate must not be empty.', $index));

                    return Command::FAILURE;
                }

                $tranche
                    ->setCommissionType($commissionType)
                    ->setCommissionRate($commissionRate)
                ;
            }

            for ($borrowerIndex = 0; $borrowerIndex < 20; ++$borrowerIndex) {
                $borrowerSiren = (string) $cells[15 + 3 * $borrowerIndex]->getValue();

                if (empty($borrowerSiren)) {
                    break;
                }

                $borrower = $this->project->findBorrowerBySiren($borrowerSiren);

                if (null === $borrower) {
                    $this->io->error(sprintf('Cannot find borrower with SIREN "%s" for tranche "%s".', $borrowerSiren, $name));

                    return Command::FAILURE;
                }

                // @todo Need to convert guaranty?
                $borrowerGuaranty     = trim($cells[16 + 3 * $borrowerIndex]->getValue());
                $borrowerShare        = new Money('EUR', (string) $cells[17 + 3 * $borrowerIndex]->getValue());
                $borrowerTrancheShare = new BorrowerTrancheShare($borrower, $tranche, $borrowerShare, $borrowerGuaranty);

                $tranche->addBorrowerShare($borrowerTrancheShare);
            }

            $this->tranches->set($index, $tranche);
        }

        $this->project->setTranches($this->tranches);

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function importParticipants(): int
    {
        $this->io->writeln('Importing participants...');

        $sheet       = $this->getSheet(self::SHEET_INDEX_PARTICIPANTS);
        $rowIterator = $sheet->getRowIterator();

        foreach ($rowIterator as $row) {
            if (1 === $rowIterator->key()) {
                continue;
            }

            $cells = $row->getCells();

            $name                     = trim($cells[0]->getValue());
            $isArranger               = 'Oui' === trim($cells[1]->getValue());
            $isDeputyArranger         = 'Oui' === trim($cells[2]->getValue());
            $isAgent                  = 'Oui' === trim($cells[3]->getValue());
            $participantCommission    = (string) $cells[4]->getValue();
            $arrangerCommission       = new NullableMoney('EUR', $cells[5]->getValue() ? (string) $cells[5]->getValue() : $cells[5]->getValue());
            $deputyArrangerCommission = new NullableMoney('EUR', $cells[6]->getValue() ? (string) $cells[6]->getValue() : $cells[6]->getValue());
            $agentCommission          = new NullableMoney('EUR', $cells[7]->getValue() ? (string) $cells[7]->getValue() : $cells[7]->getValue());
            $finalAllocation          = new Money('EUR', (string) $cells[8]->getValue()); // @todo Check whether it corresponds to the sum of allocations?

            $participantCompany = $this->companyRepository->findOneBy(['displayName' => $name]);

            if (null === $participantCompany) {
                $this->io->error(sprintf('Cannot find company "%s" as a participant.', $cells[0]->getValue()));

                return Command::FAILURE;
            }

            $participantResponsibilities = new Bitmask(0);

            if ($isArranger) {
                $participantResponsibilities->add(Participation::RESPONSIBILITY_ARRANGER);
            }

            if ($isDeputyArranger) {
                $participantResponsibilities->add(Participation::RESPONSIBILITY_DEPUTY_ARRANGER);
            }

            if ($isAgent) {
                $participantResponsibilities->add(Participation::RESPONSIBILITY_AGENT);
            }

            $participant = $this->project->findParticipationByParticipant($participantCompany);

            if (null === $participant) {
                $participant = (new Participation($this->project->getPrimaryParticipationPool(), $participantCompany, $finalAllocation));
                $this->project->addParticipation($participant);
            }

            // @todo Data consistency checks may be needed
            $participant
                ->setResponsibilities($participantResponsibilities)
                ->setArrangerCommission($isArranger ? $arrangerCommission : new NullableMoney()) // @todo Should be amounts, not percentage (CALS-3527)
                ->setDeputyArrangerCommission($isDeputyArranger ? $deputyArrangerCommission : new NullableMoney()) // @todo Should be amounts, not percentage (CALS-3527)
                ->setAgentCommission($isAgent ? $agentCommission : new NullableMoney()) // @todo Should be amounts, not percentage (CALS-3527)
                ->setParticipantCommission((string) $participantCommission)
                ->setFinalAllocation($finalAllocation) // Agent participation was created before final allocation was set so we need to overwrite it
                //->setProrata(); // @todo Mandatory?
            ;

            for ($trancheIndex = 1; $trancheIndex < 10; ++$trancheIndex) {
                // @todo Retrieve, clean and check data
                $trancheAllocation = $cells[8 + $trancheIndex]->getValue();

                if (empty($trancheAllocation)) {
                    break;
                }

                if (false === $this->tranches->containsKey($trancheIndex)) {
                    $this->io->error(sprintf('Tranche number #%d does not exist. Cannot use it for allocations.', $trancheIndex));

                    return Command::FAILURE;
                }

                $participationTrancheAllocation = new ParticipationTrancheAllocation(
                    $participant,
                    $this->tranches->get($trancheIndex),
                    new Money('EUR', (string) $trancheAllocation)
                );
                $participant->addAllocation($participationTrancheAllocation);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function importCovenants(): int
    {
        $this->io->writeln('Importing covenants...');

        $sheet       = $this->getSheet(self::SHEET_INDEX_COVENANTS);
        $rowIterator = $sheet->getRowIterator();

        foreach ($rowIterator as $row) {
            if (1 === $rowIterator->key()) {
                continue;
            }

            $cells = $row->getCells();

            $nature          = $this->getMapping($cells[0]->getValue(), self::MAPPING_COVENANT_NATURE);
            $name            = trim($cells[1]->getValue());
            $contractArticle = trim($cells[2]->getValue());
            $contractExtract = trim($cells[3]->getValue());
            $description     = trim($cells[4]->getValue());
            $startDate       = DateTimeImmutable::createFromMutable($cells[5]->getValue());
            $delay           = (int) preg_replace('/\D*/', '', $cells[6]->getValue());
            $endDate         = DateTimeImmutable::createFromMutable($cells[7]->getValue());
            $recurrence      = $this->getMapping($cells[8]->getValue(), self::MAPPING_COVENANT_RECURRENCE);
            $fixedValue      = trim($cells[10]->getValue());
            // Value type is useless because the type depends on the type of covenant
            // As long as the column was present in the first import files, it was kept in order to avoid handling multiple file formats
            // $valueType = $cells[9]->getValue();

            $covenant = (new Covenant($this->project, $name, $nature, $startDate, $delay, $endDate))
                ->setContractArticle($contractArticle)
                ->setContractExtract($contractExtract)
                ->setDescription($description)
                ->setRecurrence($recurrence)
            ;

            if ($covenant->isFinancial()) {
                if ('Oui' === $fixedValue) {
                    $covenantRuleOperator   = $this->getMapping($cells[11]->getValue(), self::MAPPING_COVENANT_RULE_OPERATOR);
                    $covenantRuleValue      = (string) $cells[12]->getValue();
                    $covenantRuleInequality = new Inequality($covenantRuleOperator, $covenantRuleValue);

                    for ($covenantRuleYear = $covenant->getStartYear(); $covenantRuleYear <= $covenant->getEndYear(); ++$covenantRuleYear) {
                        $covenantRule = new CovenantRule($covenant, $covenantRuleYear, $covenantRuleInequality);
                        $covenant->addCovenantRule($covenantRule);
                    }
                } elseif ('Non' === $fixedValue) {
                    for ($covenantRuleYear = $covenant->getStartYear(); $covenantRuleYear <= $covenant->getEndYear(); ++$covenantRuleYear) {
                        $index = $covenantRuleYear - $covenant->getStartYear();

                        $covenantRuleOperator = $this->getMapping($cells[11 + $index * 2]->getValue(), self::MAPPING_COVENANT_RULE_OPERATOR);

                        if (null === $covenantRuleOperator) {
                            $this->io->error(sprintf('Unknown rule operator "%s" for covenant "%s"', $cells[11]->getValue(), $name));

                            return Command::FAILURE;
                        }

                        $covenantRuleValue = (string) $cells[12 + $index * 2]->getValue();

                        if (empty($covenantRuleValue)) {
                            $this->io->error(sprintf('Invalid value "%s" for rule of covenant "%s" for the year %s', $covenantRuleValue, $name, $covenantRuleYear));

                            return Command::FAILURE;
                        }

                        $covenantRuleInequality = new Inequality($covenantRuleOperator, $covenantRuleValue);
                        $covenantRule           = new CovenantRule($covenant, $covenantRuleYear, $covenantRuleInequality);
                        $covenant->addCovenantRule($covenantRule);
                    }
                } else {
                    $this->io->error(sprintf('Fixed value "%s" is not correct for covenant "%s"', $fixedValue, $name));

                    return Command::FAILURE;
                }
            }

            $this->project->addCovenant($covenant);
        }

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function getSheet(int $index): Sheet
    {
        foreach ($this->sheetIterator as $sheet) {
            if ($sheet->getIndex() === $index) {
                return $sheet;
            }
        }

        throw new Exception(sprintf('Requested sheet with index %d does not exist', $index));
    }

    private function getMapping(string $value, array $map): ?string
    {
        // Trim should not be here because it is not the responsibility of this method
        // But it is easier to centralize it there
        $value = trim($value);

        return $map[$value] ?? null;
    }
}
