<?php

declare(strict_types=1);

namespace Unilend\Agency\Command;

use ArrayIterator;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\SharedStringNotFoundException;
use Box\Spout\Reader\XLSX\Sheet;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use InfiniteIterator;
use InvalidArgumentException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
use Unilend\Core\Entity\Constant\FundingSpecificity;
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
    private const SHEETS = [
        'general information',
        'borrowers',
        'participants',
        'tranches',
        'covenants',
    ];

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
        'Term Loan'                => LoanType::TERM_LOAN,
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

    // Skipped to keep data in file
    private const AMUNDI = 'Amundi';

    private ArrayCollection $tranches;

    private ProjectRepository $projectRepository;

    private CompanyRepository $companyRepository;

    private StaffRepository $staffRepository;

    private CompanyGroupTagRepository $companyGroupTagRepository;

    private UserRepository $userRepository;

    private TokenStorageInterface $tokenStorage;

    private ValidatorInterface $validator;

    private PhoneNumberUtil $phoneNumberUtil;

    public function __construct(
        ProjectRepository $projectRepository,
        CompanyRepository $companyRepository,
        StaffRepository $staffRepository,
        CompanyGroupTagRepository $companyGroupTagRepository,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        ValidatorInterface $validator,
        PhoneNumberUtil $phoneNumberUtil
    ) {
        parent::__construct();

        $this->projectRepository         = $projectRepository;
        $this->companyRepository         = $companyRepository;
        $this->staffRepository           = $staffRepository;
        $this->companyGroupTagRepository = $companyGroupTagRepository;
        $this->userRepository            = $userRepository;
        $this->tokenStorage              = $tokenStorage;
        $this->validator                 = $validator;
        $this->phoneNumberUtil           = $phoneNumberUtil;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('kls:agency:import')
            ->setDescription('This command imports agency projects from Excel files.')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run command to check import file data')
            ->addArgument('company', InputArgument::REQUIRED, 'Identifier of the agent company')
            ->addArgument('path', InputArgument::REQUIRED, 'Absolute path to the folder containing project xlsx classified by agent company')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $company = $this->companyRepository->find($input->getArgument('company'))
            ?? $this->companyRepository->findOneBy(['shortCode' => $input->getArgument('company')]);

        if (null === $company) {
            throw new InvalidArgumentException(sprintf('The company with ID "%s" was not found. Cannot start import.', $input->getArgument('company')));
        }

        $this->info($io, sprintf('Found company %s', $company->getDisplayName()));

        $path = $input->getArgument('path');

        $this->info($io, sprintf('Importing file %s...', $path));

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);

        $sheetIterator = $reader->getSheetIterator();
        $sheetIterator->rewind();

        $sheets = iterator_to_array($sheetIterator, false);

        if (count($sheets) < count(static::SHEETS)) {
            throw new Exception(sprintf('5 sheets are expected, %d found', count($sheets)));
        }

        $indexes = array_flip(static::SHEETS);

        $this->info($io, 'Importing general information...');
        $project = $this->importGeneralInformation($sheets[$indexes['general information']], $company);

        $this->info($io, 'Importing borrowers...');
        $project = $this->importBorrowers($sheets[$indexes['borrowers']], $project);

        $this->info($io, 'Importing tranches...');
        $project = $this->importTranches($sheets[$indexes['tranches']], $project);

        $this->info($io, 'Importing participants...');
        $project = $this->importParticipants($sheets[$indexes['participants']], $project);

        $this->info($io, 'Importing covenants...');
        $project = $this->importCovenants($sheets[$indexes['covenants']], $project);

        $reader->close();

        $violations = $this->validator->validate($project, null, Project::getCurrentValidationGroups($project));

        $violationsCount = count($violations);

        if ($violationsCount) {
            $io->error((string) $violations);

            return Command::FAILURE;
        }

        if (false === $dryRun) {
            $this->projectRepository->save($project);
        }

        $this->success($io, 'File successfully imported');

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     * @throws IOException
     * @throws SharedStringNotFoundException
     * @throws NonUniqueResultException
     */
    private function importGeneralInformation(Sheet $sheet, Company $company): Project
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();
        $rowIterator->next();

        $row = $rowIterator->current();

        if (null === $row) {
            throw new Exception(sprintf('There should be at least another row in general information sheet'));
        }

        $cells = $row->getCells();

        $internalRating = trim((string) $cells[3]->getValue()) ?: null;

        $companyGroupTag = $this->getMapping($cells[6]->getValue(), self::MAPPING_COMPANY_GROUP_TAG);
        $companyGroupTag = $this->companyGroupTagRepository->findOneBy(['code' => $companyGroupTag, 'companyGroup' => $company->getCompanyGroup()]);

        $fundingSpecificity = $this->getMapping($cells[7]->getValue(), self::MAPPING_FUNDING_SPECIFICITY);

        $contactEmail = trim((string) $cells[13]->getValue());

        $staff = $this->staffRepository->findOneByEmailAndCompany($contactEmail, $company);

        if (null === $staff) {
            $user = $this->userRepository->findOneBy(['email' => $contactEmail]); // No need to fetch user from project

            if (null === $user) {
                $user = new User($contactEmail);
            }

            $staff = new Staff($user, $company->getRootTeam());
        }

        $company->getRootTeam()->addStaff($staff);

        $this->staffRepository->persist($staff);

        $user  = $staff->getUser();
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'api');
        $token->setAttribute('staff', $staff);
        $token->setAttribute('company', $staff->getCompany());

        $this->tokenStorage->setToken($token);

        $closingDate        = DateTimeImmutable::createFromMutable($cells[0]->getValue());
        $contractEndDate    = DateTimeImmutable::createFromMutable($cells[1]->getValue());
        $riskGroupName      = trim((string) $cells[2]->getValue());
        $title              = trim((string) $cells[4]->getValue());
        $globalFundingMoney = new Money('EUR', (string) $cells[5]->getValue());
        $description        = trim((string) $cells[8]->getValue());
        $contactLastName    = trim((string) $cells[9]->getValue());
        $contactFirstName   = trim((string) $cells[10]->getValue());
        $contactOccupation  = trim((string) $cells[11]->getValue());
        $contactPhone       = $this->phoneNumberUtil->format($this->phoneNumberUtil->parse($cells[12]->getValue(), 'FR'), PhoneNumberFormat::E164);

        $project = (new Project($staff, $title, $riskGroupName, $globalFundingMoney, $closingDate, $contractEndDate))
            ->setInternalRatingScore($internalRating)
            ->setCompanyGroupTag($companyGroupTag)
            ->setFundingSpecificity($fundingSpecificity)
            ->setDescription($description)
        ;

        $project
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

        return $project;
    }

    /**
     * @throws Exception
     */
    private function importBorrowers(Sheet $sheet, Project $project): Project
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        $rowIterator->next(); // Jump header

        while ($rowIterator->valid() && ($row = $rowIterator->current())) {
            $cells = $row->getCells();

            $siren = preg_replace('/\D*/', '', $cells[1]->getValue());

            $name    = trim((string) $cells[0]->getValue());
            $address = trim((string) $cells[2]->getValue());
            $rcs     = trim((string) $cells[3]->getValue());
            $capital = preg_replace('/\D*/', '', $cells[5]->getValue());
            $capital = new NullableMoney('EUR', $capital);

            $borrower = (new Borrower(
                $project,
                $name,
                $cells[4]->getValue(),
                $capital,
                $address,
                $siren
            ))->setRcs($rcs);

            $project->addBorrower($borrower);

            $signatoryEmail = trim((string) $cells[9]->getValue());
            if (false === empty($signatoryEmail)) {
                $signatoryUser = $this->getProjectUser($signatoryEmail, $project)
                    ?? $this->userRepository->findOneBy(['email' => $signatoryEmail])
                    ?? (new User($signatoryEmail))
                        ->setLastName(trim((string) $cells[6]->getValue()))
                        ->setFirstName(trim((string) $cells[7]->getValue()))
                    ;

                $signatory = $borrower->findMemberByUser($signatoryUser) ?? (new BorrowerMember($borrower, $signatoryUser))
                    ->setProjectFunction(trim((string) $cells[8]->getValue()))
                    ->setSignatory(true)
                ;

                $borrower->addMember($signatory);
            }

            $referentEmail = trim((string) $cells[13]->getValue());

            if (false === empty($referentEmail)) {
                $referentUser = $this->getProjectUser($referentEmail, $project)
                    ?? $this->userRepository->findOneBy(['email' => $referentEmail])
                    ?? (new User($referentEmail))
                        ->setLastName(trim((string) $cells[10]->getValue()))
                        ->setFirstName(trim((string) $cells[11]->getValue()))
                    ;

                $referent = $borrower->findMemberByUser($referentUser) ?? (new BorrowerMember($borrower, $referentUser))->setProjectFunction(trim((string) $cells[12]->getValue()));
                $referent->setReferent(true);

                $borrower->addMember($referent);
            }

            $rowIterator->next();
        }

        return $project;
    }

    /**
     * @throws Exception
     */
    private function importTranches(Sheet $sheet, Project $project): Project
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        $rowIterator->next(); // Jump header

        $colors = new InfiniteIterator(new ArrayIterator(['#3F2865', '#F8B03B', '#E76A16', '#4BB4B4', '#235340', '#254499', '#D373BB']));
        $colors->rewind();

        while ($rowIterator->valid() && ($row = $rowIterator->current())) {
            $cells = $row->getCells();

            $rateType       = $this->getMapping($cells[6]->getValue(), self::MAPPING_RATE_TYPE);
            $name           = trim((string) $cells[1]->getValue());
            $syndicated     = 'Oui' === trim((string) $cells[2]->getValue());
            $money          = new Money('EUR', (string) $cells[3]->getValue());
            $validityDate   = $cells[4]->getValue() ? DateTimeImmutable::createFromMutable($cells[4]->getValue()) : null;
            $duration       = (int) $cells[5]->getValue();
            $loanType       = $this->getMapping($cells[10]->getValue(), self::MAPPING_LOAN_TYPE);
            $repaymentType  = $this->getMapping($cells[11]->getValue(), self::MAPPING_REPAYMENT_TYPE);
            $comment        = trim((string) $cells[14]->getValue());
            $rateMargin     = (string) $cells[7]->getValue();
            $rateFloorType  = LendingRate::INDEX_FIXED === $rateType ? null : $this->getMapping($cells[8]->getValue(), self::MAPPING_RATE_FLOOR_TYPE);
            $rateFloorValue = $rateFloorType ? (string) $cells[9]->getValue() : null;
            $trancheRate    = new LendingRate($rateType, $rateMargin, $rateFloorValue, $rateFloorType);

            $tranche = (new Tranche($project, $name, $syndicated, $colors->current(), $loanType, $repaymentType, $duration, $money, $trancheRate))
                ->setValidityDate($validityDate)
                ->setComment($comment)
            ;

            $colors->next();

            $commissionType = $this->getMapping($cells[12]->getValue(), self::MAPPING_COMMISSION_TYPE);
            $commissionRate = (string) $cells[13]->getValue();

            if (false === empty($commissionType)) {
                $tranche
                    ->setCommissionType($commissionType)
                    ->setCommissionRate($commissionRate)
                ;
            }

            for ($borrowerIndex = 0; $borrowerIndex < 20; ++$borrowerIndex) {
                $borrowerSiren = (string) $cells[15 + 3 * $borrowerIndex]->getValue();
                $borrowerSiren = preg_replace('/\D*/', '', $borrowerSiren);

                if (empty($borrowerSiren)) {
                    break;
                }

                $borrower = $project->findBorrowerBySiren($borrowerSiren);

                if (null === $borrower) {
                    throw new Exception(sprintf('Cannot find borrower with SIREN "%s" for tranche "%s".', $borrowerSiren, $name));
                }

                // @todo Need to convert guaranty?
                $borrowerGuaranty     = trim((string) $cells[16 + 3 * $borrowerIndex]->getValue());
                $borrowerShare        = new Money('EUR', (string) $cells[17 + 3 * $borrowerIndex]->getValue());
                $borrowerTrancheShare = new BorrowerTrancheShare($borrower, $tranche, $borrowerShare, $borrowerGuaranty);

                $tranche->addBorrowerShare($borrowerTrancheShare);
            }

            $project->addTranche($tranche);

            $rowIterator->next();
        }

        return $project;
    }

    /**
     * @throws Exception
     */
    private function importParticipants(Sheet $sheet, Project $project): Project
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        $rowIterator->next(); // Jump header

        while ($rowIterator->valid() && ($row = $rowIterator->current())) {
            $cells = $row->getCells();

            $name                  = str_replace('’', "'", trim((string) $cells[0]->getValue()));
            $isArranger            = 'Oui' === trim((string) $cells[1]->getValue());
            $isDeputyArranger      = 'Oui' === trim((string) $cells[2]->getValue());
            $isAgent               = 'Oui' === trim((string) $cells[3]->getValue());
            $participantCommission = (string) $cells[4]->getValue();

            $arrangerCommission       = new NullableMoney('EUR', (string) $cells[5]->getValue() ?: '0');
            $deputyArrangerCommission = new NullableMoney('EUR', (string) $cells[6]->getValue() ?: '0');
            $agentCommission          = new NullableMoney('EUR', (string) $cells[7]->getValue() ?: '0');

            $finalAllocation = new Money('EUR', (string) $cells[8]->getValue());

            if (static::AMUNDI === $name) {
                $rowIterator->next();

                continue;
            }
            // Handle ' and ’ in display name
            $participantCompany = $this->companyRepository->findOneBy(['displayName' => $name])
                ?? $this->companyRepository->findOneBy(['displayName' => trim((string) $cells[0]->getValue())]);

            if (null === $participantCompany) {
                throw new Exception(sprintf('Cannot find company "%s" (%s) as a participant.', $name, $cells[0]->getValue()));
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

            $participant = $project->findParticipationByParticipant($participantCompany);

            if (null === $participant) {
                $participant = (new Participation($project->getPrimaryParticipationPool(), $participantCompany, $finalAllocation));
                $project->addParticipation($participant);
            }

            // @todo Data consistency checks may be needed
            $participant
                ->setResponsibilities($participantResponsibilities)
                ->setArrangerCommission($isArranger ? $arrangerCommission : new NullableMoney()) // @todo Should be amounts, not percentage (CALS-3527)
                ->setDeputyArrangerCommission($isDeputyArranger ? $deputyArrangerCommission : new NullableMoney()) // @todo Should be amounts, not percentage (CALS-3527)
                ->setAgentCommission($isAgent ? $agentCommission : new NullableMoney()) // @todo Should be amounts, not percentage (CALS-3527)
                ->setParticipantCommission($participantCommission)
                ->setFinalAllocation($finalAllocation) // Agent participation was created before final allocation was set so we need to overwrite it
                //->setProrata(); // @todo Mandatory?
            ;

            for ($trancheIndex = 1; $trancheIndex < 10; ++$trancheIndex) {
                // @todo Retrieve, clean and check data
                $trancheAllocation = $cells[8 + $trancheIndex]->getValue();

                if (empty($trancheAllocation)) {
                    break;
                }

                if (false === $project->getTranches()->containsKey($trancheIndex - 1)) {
                    throw new Exception(sprintf('Tranche number #%d does not exist. Cannot use it for allocations.', $trancheIndex));
                }

                $participationTrancheAllocation = new ParticipationTrancheAllocation(
                    $participant,
                    $project->getTranches()[$trancheIndex - 1],
                    new Money('EUR', (string) $trancheAllocation)
                );
                $participant->addAllocation($participationTrancheAllocation);
            }

            $rowIterator->next();
        }

        return $project;
    }

    /**
     * @throws Exception
     */
    private function importCovenants(Sheet $sheet, Project $project): Project
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        $rowIterator->next(); // Jump header

        while ($rowIterator->valid() && ($row = $rowIterator->current())) {
            $cells = $row->getCells();

            $nature          = $this->getMapping($cells[0]->getValue(), self::MAPPING_COVENANT_NATURE);
            $name            = trim((string) $cells[1]->getValue());
            $contractArticle = trim((string) $cells[2]->getValue());
            $contractExtract = trim((string) $cells[3]->getValue());
            $description     = trim((string) $cells[4]->getValue());
            $startDate       = DateTimeImmutable::createFromMutable($cells[5]->getValue());
            $delay           = (int) preg_replace('/\D*/', '', $cells[6]->getValue()) ?: 1; //  TODO Discuss with metier about this
            $recurrence      = $this->getMapping($cells[8]->getValue(), self::MAPPING_COVENANT_RECURRENCE);
            $endDate         = null === $recurrence ? $project->getContractEndDate() : null;
            $endDate         = $cells[7]->getValue() ? DateTimeImmutable::createFromMutable($cells[7]->getValue()) : $endDate;
            if (null === $endDate) {
                throw new Exception(sprintf('You must have an enddate if there is recurrence (line %d)', $rowIterator->key()));
            }
            // Value type is useless because the type depends on the type of covenant
            // As long as the column was present in the first import files, it was kept in order to avoid handling multiple file formats
            // $valueType = $cells[9]->getValue();

            $covenant = (new Covenant($project, $name, $nature, $startDate, $delay, $endDate))
                ->setContractArticle($contractArticle)
                ->setContractExtract($contractExtract)
                ->setDescription($description)
                ->setRecurrence($recurrence)
            ;

            if ($covenant->isFinancial()) {
                $fixedValue = trim((string) $cells[10]->getValue());
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

                        if (empty($covenantRuleOperator)) {
                            throw new Exception(sprintf(
                                'Missing conventRule operator. There should be a value at cell (%d, %d) since the covenant span %d years',
                                $rowIterator->key(),
                                11 + $index * 2,
                                $covenant->getEndYear() - $covenant->getStartYear() + 1
                            ));
                        }

                        $covenantRuleValue = (string) $cells[12 + $index * 2]->getValue();

                        if (empty($covenantRuleValue)) {
                            throw new Exception(sprintf(
                                'Missing conventRule value. There should be a value at cell (%d, %d) since the covenant span %d years',
                                $rowIterator->key(),
                                12 + $index * 2,
                                $covenant->getEndYear() - $covenant->getStartYear() + 1
                            ));
                        }

                        $covenantRuleInequality = new Inequality($covenantRuleOperator, $covenantRuleValue);
                        $covenantRule           = new CovenantRule($covenant, $covenantRuleYear, $covenantRuleInequality);
                        $covenant->addCovenantRule($covenantRule);
                    }
                } else {
                    throw new InvalidArgumentException(sprintf('Fixed value "%s" is not correct for covenant "%s"', $fixedValue, $name));
                }
            }

            $project->addCovenant($covenant);

            $rowIterator->next();
        }

        return $project;
    }

    private function info(SymfonyStyle $output, string $message)
    {
        if ($output->isVerbose()) {
            $output->info($message);
        }
    }

    private function success(SymfonyStyle $output, string $message)
    {
        if ($output->isVerbose()) {
            $output->success($message);
        }
    }

    private function getMapping(string $value, array $map): ?string
    {
        // Trim should not be here because it is not the responsibility of this method
        // But it is easier to centralize it there
        $value = trim($value);

        return $map[$value] ?? null;
    }

    private function getProjectUser(string $email, Project $project): ?User
    {
        if ($project->getAddedBy()->getUser()->getEmail() === $email) {
            return $project->getAddedBy()->getUser();
        }

        foreach ([$project->getBorrowers(), $project->getParticipations(), $project->getAgent()] as $collection) {
            foreach ($collection as $abstractProjectPartaker) {
                foreach ($abstractProjectPartaker->getMembers() as $member) {
                    if ($member->getUser()->getEmail() === $email) {
                        return $member->getUser();
                    }
                }
            }
        }

        return null;
    }
}
