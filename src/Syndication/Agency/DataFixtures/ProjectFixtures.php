<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\LoanType;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\Core\Entity\Embeddable\LendingRate;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use KLS\Syndication\Agency\Entity\Borrower;
use KLS\Syndication\Agency\Entity\BorrowerMember;
use KLS\Syndication\Agency\Entity\BorrowerTrancheShare;
use KLS\Syndication\Agency\Entity\Covenant;
use KLS\Syndication\Agency\Entity\CovenantRule;
use KLS\Syndication\Agency\Entity\Embeddable\Inequality;
use KLS\Syndication\Agency\Entity\MarginImpact;
use KLS\Syndication\Agency\Entity\MarginRule;
use KLS\Syndication\Agency\Entity\Participation;
use KLS\Syndication\Agency\Entity\ParticipationTrancheAllocation;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Entity\Tranche;
use KLS\Syndication\Common\Constant\Modality\ParticipationType;
use KLS\Syndication\Common\Constant\Modality\SyndicationType;
use KLS\Syndication\Common\Constant\Tranche\RepaymentType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ValidatorInterface $validator;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        parent::__construct($tokenStorage);
        $this->validator       = $validator;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);

        $this->login($staff);

        $draftProject = $this->createProject($staff);
        $this->forcePublicId($draftProject, 'draft');

        $publishableProject = $this->createPublishableProject($staff, $manager);
        $this->forcePublicId($publishableProject, 'publishable');

        $publishedProject = $this->createPublishableProject($staff, $manager);
        $this->forcePublicId($publishedProject, 'published');

        // Fix email for one borrower to easy connect as him
        $publishedProject->getBorrowers()[0]->getMembers()[0]->getUser()->setEmail('user42@borrower.com');

        $finishedProject = $this->createPublishableProject($staff, $manager);
        $this->forcePublicId($finishedProject, 'finished');

        $archivedProject = $this->createPublishableProject($staff, $manager);
        $this->forcePublicId($archivedProject, 'archived');

        // There are multiple save to let the doctrine listener trigger

        $this->save($manager, $draftProject);
        $this->save($manager, $publishableProject);
        $this->save($manager, $publishedProject);
        $this->save($manager, $finishedProject);
        $this->save($manager, $archivedProject);

        $publishedProject->publish();

        // TODO Rework this part of the fixtures (a builder or a factory would be more appropriate)
        foreach (
            [
                Covenant::NATURE_FINANCIAL_ELEMENT,
                Covenant::NATURE_FINANCIAL_RATIO,
                Covenant::NATURE_DOCUMENT, Covenant::NATURE_CONTROL,
            ] as $nature
        ) {
            $unpublishedCovenant = $this->createCovenant(
                $publishedProject,
                $nature,
                Covenant::RECURRENCE_12M,
                new DateTimeImmutable('-2 years')
            );
            $unpublishedCovenant->setName($nature . '-unpublished');
            $publishedProject->addCovenant($unpublishedCovenant);

            if ($unpublishedCovenant->isFinancial()) {
                foreach (\range($unpublishedCovenant->getStartYear(), $unpublishedCovenant->getEndYear()) as $year) {
                    $unpublishedCovenant->addCovenantRule($this->createCovenantRule($unpublishedCovenant, $year));
                }
            }

            $publishedCovenant = $this->createCovenant(
                $publishedProject,
                $nature,
                Covenant::RECURRENCE_12M,
                new DateTimeImmutable('-2 years')
            );
            $publishedCovenant->setName($nature . '-published');
            $publishedCovenant->publish();
            $publishedProject->addCovenant($publishedCovenant);

            if ($publishedCovenant->isFinancial()) {
                foreach (\range($publishedCovenant->getStartYear(), $publishedCovenant->getEndYear()) as $year) {
                    $publishedCovenant->addCovenantRule($this->createCovenantRule($publishedCovenant, $year));
                }
            }

            foreach ([true, false] as $validation) {
                $invalidRaisons = false === $validation ? ['grantedDelay', 'breach', 'waiver', null] : [null];
                foreach ($invalidRaisons as $raison) {
                    foreach ([true, false] as $shared) {
                        $archivedOptions = $shared ? [true, false] : [false];
                        foreach ($archivedOptions as $archived) {
                            $covenant = $this->createCovenant(
                                $publishedProject,
                                $nature,
                                Covenant::RECURRENCE_12M,
                                new DateTimeImmutable('-1 years')
                            );

                            $name = $nature . ($validation ? '-yes' : '-no')
                                . ($raison ? '-' . $raison : '')
                                . ($shared ? '-shared' : '')
                                . ($archived ? '-archived' : '');
                            $covenant->setName($name);
                            $covenant->publish();

                            if ($covenant->isFinancial()) {
                                foreach (\range($covenant->getStartYear(), $covenant->getEndYear()) as $year) {
                                    $covenant->addCovenantRule($this->createCovenantRule($covenant, $year));
                                }
                            }

                            foreach ($covenant->getTerms() as $term) {
                                if ($term->getStartDate() >= new DateTimeImmutable()) {
                                    continue;
                                }
                                $term->setValidation($validation);

                                switch ($raison) {
                                    case 'grantedDelay':
                                        $term->setGrantedDelay(90);

                                        break;

                                    case 'breach':
                                        $term->setBreach(true);

                                        break;

                                    case 'waiver':
                                        $term->setBreach(true);
                                        $term->setWaiver(true);

                                        break;
                                }

                                if ($shared) {
                                    $term->share();

                                    if ($archived) {
                                        $term->archive();
                                    }
                                }
                            }
                            $publishedProject->addCovenant($covenant);
                        }
                    }
                }
            }
        }
        $invalidCovenant = $this->createCovenant(
            $publishedProject,
            Covenant::NATURE_CONTROL,
            Covenant::RECURRENCE_3M,
            new DateTimeImmutable('-2 years')
        );
        $invalidCovenant->publish();
        $invalidCovenant->getTerms()[0]->setValidation(false);
        $invalidCovenant->getTerms()[1]->setValidation(true)->share();
        $invalidCovenant->getTerms()[4]->setValidation(false)->share();
        $invalidCovenant->getTerms()[2]->setValidation(false)->share()->archive();
        $invalidCovenant->getTerms()[3]->setValidation(true)->share()->archive();
        $publishedProject->addCovenant($invalidCovenant);

        $breachCovenant = $this->createCovenant(
            $publishedProject,
            Covenant::NATURE_CONTROL,
            Covenant::RECURRENCE_12M,
            new DateTimeImmutable('-3 years')
        );
        $breachCovenant->publish();
        $breachCovenant->getTerms()[0]->setValidation(false);
        $breachCovenant->getTerms()[0]->setBreach(true);

        $breachCovenant->getTerms()[1]->setValidation(false);
        $breachCovenant->getTerms()[1]->setBreach(true);
        $breachCovenant->getTerms()[1]->share();

        $breachCovenant->getTerms()[2]->setValidation(false);
        $breachCovenant->getTerms()[2]->setBreach(true);
        $breachCovenant->getTerms()[2]->share()->archive();
        $publishedProject->addCovenant($breachCovenant);

        $waiverCovenant = $this->createCovenant(
            $publishedProject,
            Covenant::NATURE_CONTROL,
            Covenant::RECURRENCE_12M,
            new DateTimeImmutable('-1 years')
        );
        $waiverCovenant->publish();
        $waiverCovenant->getTerms()[0]->setValidation(false);
        $waiverCovenant->getTerms()[0]->setBreach(true);
        $waiverCovenant->getTerms()[0]->setWaiver(true);
        $publishedProject->addCovenant($waiverCovenant);

        $finishedProject->publish();
        $archivedProject->publish();

        $this->save($manager, $publishedProject);
        $this->save($manager, $finishedProject);
        $this->save($manager, $archivedProject);

        $finishedProject->finish();
        $this->save($manager, $finishedProject);

        $archivedProject->archive();
        $this->save($manager, $archivedProject);

        $facturationProjects = [
            '10-04-2022' => null,
            '10-05-2022' => '15-05-2022',
            '15-05-2021' => '20-05-2022',
            '25-05-2019' => '10-06-2021',
            '15-04-2020' => '05-04-2022',
            '20-04-2020' => '05-03-2022',
        ];

        foreach ($facturationProjects as $start => $end) {
            $project = $this->createPublishableProject($staff, $manager);

            $this->forcePropertyValue($project, 'added', new DateTimeImmutable($start));

            $this->save($manager, $project);

            foreach ($project->getStatuses() as $statusHistory) {
                if (Project::STATUS_DRAFT === $statusHistory->getStatus()) {
                    $this->forcePropertyValue($statusHistory, 'added', new DateTimeImmutable($start));
                    $this->save($manager, $statusHistory);
                }
            }

            if ($end) {
                $project->publish();

                $this->save($manager, $project);

                foreach ($project->getStatuses() as $statusHistory) {
                    if (Project::STATUS_PUBLISHED === $statusHistory->getStatus()) {
                        $this->forcePropertyValue(
                            $statusHistory,
                            'added',
                            (new DateTimeImmutable($end))->modify('- 2 day')
                        );
                        $this->save($manager, $statusHistory);
                    }
                }

                $project->finish();

                $this->save($manager, $project);

                foreach ($project->getStatuses() as $statusHistory) {
                    if (Project::STATUS_FINISHED === $statusHistory->getStatus()) {
                        $this->forcePropertyValue($statusHistory, 'added', new DateTimeImmutable($end));
                        $this->save($manager, $statusHistory);
                    }
                }
            }
        }
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
        ];
    }

    /**
     * @param mixed $object
     *
     * @throws Exception
     */
    private function save(ObjectManager $manager, $object)
    {
        $validationGroups = $object instanceof Project ? Project::getCurrentValidationGroups($object) : [];

        $violations = $this->validator->validate($object, null, $validationGroups);

        if ($violations->count()) {
            throw new Exception(\sprintf('%s %s %s', $object->getPublicId(), PHP_EOL, $violations));
        }

        $manager->persist($object);

        $manager->flush();

        // Refresh is needed for validation (mimics the normal process of update spanned over multiple request)
        $manager->refresh($object);
    }

    /**
     * @throws Exception
     */
    private function createProject(Staff $staff): Project
    {
        $project = new Project(
            $staff,
            $this->faker->title,
            $this->faker->name,
            new Money('EUR', '5000000'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+1 year'),
        );

        $project->setCompanyGroupTag($staff->getAvailableCompanyGroupTags()[0]);

        return $project;
    }

    /**
     * @throws Exception
     */
    private function createPublishableProject(Staff $staff, ObjectManager $manager): Project
    {
        $project            = $this->createProject($staff);
        $agentParticipation = $project->getAgentParticipation();
        $agentParticipation->setLegalForm(LegalForm::SARL);
        $agentParticipation->setHeadOffice($this->faker->address);
        $agentParticipation->getBankAccount()
            ->setBankAddress($this->faker->address)
            ->setIban($this->faker->iban('fr'))
            ->setBankInstitution($this->faker->company)
            ->setBic('AGRIFRPP907')
        ;
        $agentParticipation->setCorporateName($this->faker->name);

        $this->withPublishableAgentData($project)
            ->withPublishableBorrowers($project, $manager)
            ->withPublishableTranches($project)
            ->withPublishableBorrowerTrancheShare($project)
            ->withPublishableCovenants($project)
            ->withPublishableParticipations($project)
            ->withPublishableSyndicationModality($project)
            ->withPublishableParticipationTrancheAllocation($project)
        ;

        return $project;
    }

    private function withPublishableParticipations(Project $project): ProjectFixtures
    {
        $participations = [
            ...\array_map(
                fn ($company) => $this->createParticipation($project, $this->getReference($company)),
                [
                    CompanyFixtures::COMPANY_MANY_STAFF,
                    CompanyFixtures::COMPANY_MANY_STAFF,
                    CompanyFixtures::COMPANIES[0],
                ]
            ),
            ...\array_map(
                fn ($company) => $this->createParticipation($project, $this->getReference($company), true),
                [CompanyFixtures::COMPANIES[4], CompanyFixtures::COMPANY_MANY_STAFF, CompanyFixtures::COMPANIES[3]]
            ),
        ];

        foreach ($participations as $participation) {
            $project->addParticipation($participation);
        }

        return $this;
    }

    private function withPublishableParticipationTrancheAllocation(Project $project): ProjectFixtures
    {
        /** @var Participation $participation */
        foreach ($project->getParticipations() as $participation) {
            foreach ($project->getTranches() as $tranche) {
                $participation->addAllocation(
                    new ParticipationTrancheAllocation(
                        $participation,
                        $tranche,
                        new Money('EUR', '20000')
                    )
                );
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function withPublishableCovenants(Project $project): ProjectFixtures
    {
        $financialCovenant = $this->createCovenant(
            $project,
            Covenant::NATURE_FINANCIAL_ELEMENT,
            Covenant::RECURRENCE_12M
        );

        $covenantRules = \array_map(
            fn ($index) => $this->createCovenantRule($financialCovenant, $index),
            \range($financialCovenant->getStartYear(), $financialCovenant->getEndYear())
        );

        foreach ($covenantRules as $covenantRule) {
            $financialCovenant->addCovenantRule($covenantRule);
            $financialCovenant->addMarginRule($this->createMarginRule($financialCovenant, $project->getTranches()));
        }

        $covenants = [
            $this->createCovenant($project, Covenant::NATURE_DOCUMENT, Covenant::RECURRENCE_12M),
            $this->createCovenant($project, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_12M),
            $this->createCovenant(
                $project,
                Covenant::NATURE_DOCUMENT,
                Covenant::RECURRENCE_12M,
                new DateTimeImmutable('-1 years')
            ),
            $this->createCovenant(
                $project,
                Covenant::NATURE_CONTROL,
                Covenant::RECURRENCE_12M,
                new DateTimeImmutable('-1 years')
            ),
            $financialCovenant,
        ];

        foreach ($covenants as $covenant) {
            $project->addCovenant($covenant);
        }

        return $this;
    }

    private function withPublishableAgentData(Project $project): ProjectFixtures
    {
        $agent = $project->getAgent();

        $agent->getContact()
            ->setEmail($this->faker->email)
            ->setPhone('+33600000000')
            ->setOccupation('agent')
        ;

        $agent->getBankAccount()
            ->setIban($this->faker->iban('fr'))
            ->setBic('AGRIFRPP907')
            ->setBankInstitution('bank institution')
            ->setBankAddress($this->faker->address)
        ;

        $agent
            ->setLegalForm(LegalForm::EURL)
            ->setHeadOffice($this->faker->address)
            ->setRcs(\implode(
                ' ',
                [
                    'RCS',
                    \mb_strtoupper($this->faker->city),
                    $this->faker->randomDigit % 2 ? 'A' : 'B',
                    $project->getAgent()->getMatriculationNumber(),
                ]
            ))
            ->setCapital(new NullableMoney('EUR', '300000'))
            ->setVariableCapital(null)
            ->setCorporateName($this->faker->company)
        ;

        return $this;
    }

    private function withPublishableSyndicationModality(Project $project): ProjectFixtures
    {
        $project->getPrimaryParticipationPool()->setSyndicationType(SyndicationType::PRIMARY);
        $project->getPrimaryParticipationPool()->setParticipationType(ParticipationType::DIRECT);

        if ($project->hasSilentSyndication()) {
            $project->getSecondaryParticipationPool()->setSyndicationType(SyndicationType::PRIMARY);
            $project->getSecondaryParticipationPool()->setParticipationType(ParticipationType::DIRECT);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function withPublishableBorrowers(Project $project, ObjectManager $manager): ProjectFixtures
    {
        $borrowers = new ArrayCollection();

        foreach (\range(0, 3) as $index) {
            $borrower = $this->createBorrower($project);

            $borrower->addMember($this->createBorrowerMember($borrower, $manager, true));
            $borrower->addMember($this->createBorrowerMember($borrower, $manager, false, true));
            $borrower->addMember($this->createBorrowerMember($borrower, $manager));

            $borrowers[] = $borrower;
        }

        $project->setBorrowers($borrowers);

        return $this;
    }

    private function withPublishableTranches(Project $project): ProjectFixtures
    {
        $tranches = \array_map(fn () => $this->createTranche($project), \range(0, 4));

        $project->setTranches(new ArrayCollection($tranches));

        return $this;
    }

    private function withPublishableBorrowerTrancheShare(Project $project): ProjectFixtures
    {
        foreach ($project->getBorrowers() as $borrower) {
            foreach ($project->getTranches() as $tranche) {
                $tranche->addBorrowerShare(new BorrowerTrancheShare($borrower, $tranche, new Money('EUR', '30000000')));
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function createBorrower(Project $project): Borrower
    {
        $borrower = new Borrower(
            $project,
            $this->faker->company,
            'SARL',
            $this->faker->address,
            $this->faker->siren(false), // Works because Faker is set to Fr_fr.
        );

        $borrower->getBankAccount()
            ->setBankAddress($this->faker->address)
            ->setIban($this->faker->iban('fr'))
            ->setBankInstitution($this->faker->company)
            ->setBic('AGRIFRPP907')
        ;

        return $borrower;
    }

    /**
     * @throws Exception
     */
    private function createBorrowerMember(
        Borrower $borrower,
        ObjectManager $manager,
        bool $referent = false,
        bool $signatory = false
    ): BorrowerMember {
        $user = new User($this->faker->email);
        $user->setLastName($this->faker->lastName);
        $user->setFirstName($this->faker->firstName);
        $user->setPhone('+33600000000');
        $user->setJobFunction('Job function');
        $user->setPlainPassword('0000');
        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPlainPassword()));
        $user->setCurrentStatus(new UserStatus($user, UserStatus::STATUS_CREATED));

        $manager->persist($user);
        $manager->flush();

        $borrowerMember = new BorrowerMember($borrower, $user);
        $borrowerMember->setReferent($referent);
        $borrowerMember->setSignatory($signatory);

        return $borrowerMember;
    }

    /**
     * @throws Exception
     */
    private function createTranche(Project $project): Tranche
    {
        $tranche = new Tranche(
            $project,
            $this->faker->name,
            $this->faker->hexColor,
            LoanType::TERM_LOAN,
            RepaymentType::ATYPICAL,
            $this->faker->numberBetween(1, 40),
            new Money('EUR', (string) $this->faker->numberBetween(3000000, 4000000)),
            new LendingRate(LendingRate::INDEX_FIXED, (string) $this->faker->randomFloat(4, 0.1, 0.90)),
        );

        $tranche->setValidityDate(new DateTimeImmutable('+ 1 month'));
        $tranche->setDraw(new NullableMoney($project->getCurrency(), '400000'));

        return $tranche;
    }

    /**
     * @throws Exception
     */
    private function createParticipation(
        Project $project,
        Company $participant,
        bool $secondary = false
    ): Participation {
        $participation = new Participation(
            $project->getParticipationPools()[$secondary],
            $participant
        );

        $participation->setLegalForm(LegalForm::SARL)
            ->setCorporateName($this->faker->name)
            ->setHeadOffice($this->faker->address)
        ;

        $participation->getBankAccount()
            ->setBankAddress($this->faker->address)
            ->setIban($this->faker->iban('fr'))
            ->setBankInstitution($this->faker->company)
            ->setBic('AGRIFRPP907')
        ;

        return $participation;
    }

    /**
     * @throws Exception
     */
    private function createCovenant(
        Project $project,
        string $nature,
        ?string $recurrence = null,
        ?DateTimeImmutable $startDate = null
    ): Covenant {
        $covenant = new Covenant(
            $project,
            $this->faker->title,
            $nature,
            $startDate ?? new DateTimeImmutable('now'),
            90,
            new DateTimeImmutable('+ 1 years')
        );

        $covenant->setRecurrence($recurrence);

        return $covenant;
    }

    private function createCovenantRule(Covenant $covenant, int $year): CovenantRule
    {
        return new CovenantRule(
            $covenant,
            $year,
            new Inequality(MathOperator::INFERIOR_OR_EQUAL, '0.9')
        );
    }

    private function createMarginRule(Covenant $covenant, iterable $tranches): MarginRule
    {
        $marginRule = new MarginRule($covenant, new Inequality(MathOperator::INFERIOR_OR_EQUAL, '0.9'));

        foreach ($tranches as $tranche) {
            $marginImpact = new MarginImpact($marginRule, $tranche, '0.9');
            $marginRule->addImpact($marginImpact);
        }

        return $marginRule;
    }
}
