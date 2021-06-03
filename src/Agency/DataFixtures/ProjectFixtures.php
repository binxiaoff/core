<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Unilend\Agency\Entity\Borrower;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\BorrowerTrancheShare;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\CovenantRule;
use Unilend\Agency\Entity\Embeddable\Inequality;
use Unilend\Agency\Entity\MarginImpact;
use Unilend\Agency\Entity\MarginRule;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationTrancheAllocation;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Tranche;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\CompanyFixtures;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Constant\LegalForm;
use Unilend\Core\Entity\Constant\MathOperator;
use Unilend\Core\Entity\Constant\SyndicationModality\ParticipationType;
use Unilend\Core\Entity\Constant\SyndicationModality\SyndicationType;
use Unilend\Core\Entity\Constant\Tranche\LoanType;
use Unilend\Core\Entity\Constant\Tranche\RepaymentType;
use Unilend\Core\Entity\Embeddable\LendingRate;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ValidatorInterface $validator;

    public function __construct(TokenStorageInterface $tokenStorage, ValidatorInterface $validator)
    {
        parent::__construct($tokenStorage);
        $this->validator = $validator;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);

        $this->login($staff);

        $draftProject = $this->createProject($staff);
        $this->forcePublicId($draftProject, 'draft');

        $publishableProject = $this->createPublishableProject($staff);
        $this->forcePublicId($publishableProject, 'publishable');

        $publishedProject = $this->createPublishableProject($staff);
        $this->forcePublicId($publishedProject, 'published');

        $finishedProject = $this->createPublishableProject($staff);
        $this->forcePublicId($finishedProject, 'finished');

        $archivedProject = $this->createPublishableProject($staff);
        $this->forcePublicId($archivedProject, 'archived');

        // There are multiple save to let the doctrine listener trigger

        $this->save($manager, $draftProject);
        $this->save($manager, $publishableProject);
        $this->save($manager, $publishedProject);
        $this->save($manager, $finishedProject);
        $this->save($manager, $archivedProject);

        $publishedProject->publish();

        $invalidCovenant = $this->createCovenant($publishedProject, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M, new DateTimeImmutable('-10 years'));
        $invalidCovenant->publish();
        $invalidCovenant->getTerms()[0]->setValidation(false);
        $invalidCovenant->getTerms()[1]->setValidation(true)->share();
        $invalidCovenant->getTerms()[4]->setValidation(false)->share();
        $invalidCovenant->getTerms()[2]->setValidation(false)->share()->archive();
        $invalidCovenant->getTerms()[3]->setValidation(true)->share()->archive();
        $publishedProject->addCovenant($invalidCovenant);

        $breachCovenant = $this->createCovenant($publishedProject, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M, new DateTimeImmutable('-10 years'));
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

        $waiverCovenant = $this->createCovenant($publishedProject, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M, new DateTimeImmutable('-10 years'));
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
     * @throws Exception
     */
    private function save(ObjectManager $manager, Project $object)
    {
        $violations = $this->validator->validate($object, null, Project::getCurrentValidationGroups($object));

        if ($violations->count()) {
            throw new Exception(sprintf('%s %s %s', $object->getPublicId(), PHP_EOL, $violations));
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
            new DateTimeImmutable('+1 year')
        );

        $project->setCompanyGroupTag($staff->getAvailableCompanyGroupTags()[0]);

        return $project;
    }

    /**
     * @throws Exception
     */
    private function createPublishableProject(Staff $staff): Project
    {
        $project = $this->createProject($staff);
        $this->withPublishableAgentData($project)
            ->withPublishableBorrowers($project)
            ->withPublishableTranches($project)
            ->withPublishableBorrowerTrancheShare($project)
            ->withPublishableSyndicationModality($project)
            ->withPublishableCovenants($project)
            ->withPublishableParticipations($project)
            ->withPublishableParticipationTrancheAllocation($project)
        ;

        return $project;
    }

    private function withPublishableParticipations(Project $project): ProjectFixtures
    {
        $participations = [
            ...array_map(
                fn ($company) => $this->createParticipation($project, $this->getReference($company)),
                [CompanyFixtures::COMPANY_MANY_STAFF, CompanyFixtures::COMPANIES[0]]
            ),
            ...array_map(
                fn ($company) => $this->createParticipation($project, $this->getReference($company), true),
                [CompanyFixtures::COMPANIES[4], CompanyFixtures::COMPANIES[3]]
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
                if ($tranche->isSyndicated()) {
                    $participation->addAllocation(new ParticipationTrancheAllocation($participation, $tranche, new Money('EUR', '20000')));
                }
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function withPublishableCovenants(Project $project): ProjectFixtures
    {
        $financialCovenant = $this->createCovenant($project, Covenant::NATURE_FINANCIAL_ELEMENT, Covenant::RECURRENCE_12M);

        $covenantRules = array_map(
            fn ($index) => $this->createCovenantRule($financialCovenant, $index),
            range($financialCovenant->getStartYear(), $financialCovenant->getEndYear())
        );

        foreach ($covenantRules as $covenantRule) {
            $financialCovenant->addCovenantRule($covenantRule);
            $financialCovenant->addMarginRule($this->createMarginRule($financialCovenant, $project->getTranches()));
        }

        $covenants = [
            $this->createCovenant($project, Covenant::NATURE_DOCUMENT, Covenant::RECURRENCE_3M),
            $this->createCovenant($project, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M),
            $this->createCovenant($project, Covenant::NATURE_DOCUMENT, Covenant::RECURRENCE_3M, new DateTimeImmutable('-3 years')),
            $this->createCovenant($project, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M, new DateTimeImmutable('-3 years')),
            $financialCovenant,
        ];

        foreach ($covenants as $covenant) {
            $project->addCovenant($covenant);
        }

        return $this;
    }

    private function withPublishableAgentData(Project $project): ProjectFixtures
    {
        $project->getAgent()->getContact()
            ->setEmail($this->faker->email)
            ->setPhone('+33600000000')
            ->setOccupation('agent')
        ;
        $project->getAgent()
            ->setLegalForm(LegalForm::EURL)
            ->setIban('DE66641502668879073767')
            ->setBic('AGRIFRPP907')
            ->setHeadOffice($this->faker->address)
            ->setRcs(implode(' ', ['RCS', mb_strtoupper($this->faker->city), $this->faker->randomDigit % 2 ? 'A' : 'B', $project->getAgent()->getMatriculationNumber()]))
            ->setCapital(new Money('EUR', '0'))
            ->setBankInstitution('bank institution')
            ->setBankAddress($this->faker->address)
            ->setCorporateName($this->faker->company)
        ;

        return $this;
    }

    private function withPublishableSyndicationModality(Project $project)
    {
        $project->getPrimaryParticipationPool()->setSyndicationType(SyndicationType::PRIMARY);
        $project->getPrimaryParticipationPool()->setParticipationType(ParticipationType::DIRECT);

        return $this;
    }

    /**
     * @throws Exception
     */
    private function withPublishableBorrowers(Project $project): ProjectFixtures
    {
        $borrowers = new ArrayCollection();

        foreach (range(0, 3) as $index) {
            $borrower = $this->createBorrower($project);

            $borrower->addMember($this->createBorrowerMember($borrower, true));
            $borrower->addMember($this->createBorrowerMember($borrower, false, true));
            $borrower->addMember($this->createBorrowerMember($borrower));

            $borrowers[] = $borrower;
        }

        $project->setBorrowers($borrowers);

        return $this;
    }

    private function withPublishableTranches(Project $project): ProjectFixtures
    {
        $tranches = array_map(fn () => $this->createTranche($project), range(0, 4));

        $tranches[0]->setSyndicated(false);

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
            new Money(
                $project->getCurrency(),
                (string) $this->faker->randomFloat(0, 100000)
            ),
            $this->faker->address,
            $this->faker->siren(false), // Works because Faker is set to Fr_fr.
        );

        $borrower->setBankAddress($this->faker->address)
            ->setIban('DE66641502668879073767')
            ->setBankInstitution($this->faker->company)
            ->setBic('AGRIFRPP907')
        ;

        return $borrower;
    }

    private function createBorrowerMember(Borrower $borrower, bool $referent = false, bool $signatory = false): BorrowerMember
    {
        $borrowerMember = new BorrowerMember($borrower, new User($this->faker->email));
        $borrowerMember->setReferent($referent);
        $borrowerMember->setSignatory($signatory);

        return $borrowerMember;
    }

    /**
     * @throws Exception
     *
     * @return Tranche
     */
    private function createTranche(Project $project)
    {
        return new Tranche(
            $project,
            $this->faker->name,
            true,
            $this->faker->hexColor,
            LoanType::TERM_LOAN,
            RepaymentType::ATYPICAL,
            $this->faker->numberBetween(1, 40),
            new Money('EUR', (string) $this->faker->numberBetween(3000000, 4000000)),
            new LendingRate(LendingRate::INDEX_FIXED, (string) $this->faker->randomFloat(4, 0.1, 0.90)),
        );
    }

    /**
     * @throws Exception
     */
    private function createParticipation(Project $project, Company $participant, bool $secondary = false): Participation
    {
        return new Participation(
            $project->getParticipationPools()[$secondary],
            $participant,
            new Money('EUR', (string) $this->faker->numberBetween(100000)),
            new Money('EUR', (string) $this->faker->numberBetween(40000000)),
        );
    }

    /**
     * @throws Exception
     *
     * @return Covenant
     */
    private function createCovenant(Project $project, string $nature, ?string $recurrence = null, ?DateTimeImmutable $startDate = null)
    {
        $covenant = new Covenant(
            $project,
            $this->faker->title,
            $nature,
            $startDate ?? new DateTimeImmutable('now'),
            90,
            DateTimeImmutable::createFromMutable($this->faker->dateTimeInInterval('+2 years', '+6 years')),
        );

        $covenant->setRecurrence($recurrence);

        return $covenant;
    }

    /**
     * @return CovenantRule
     */
    private function createCovenantRule(Covenant $covenant, int $year)
    {
        return new CovenantRule(
            $covenant,
            $year,
            new Inequality(MathOperator::INFERIOR_OR_EQUAL, '0.9')
        );
    }

    /**
     * @return MarginRule
     */
    private function createMarginRule(Covenant $covenant, iterable $tranches)
    {
        $marginRule = new MarginRule($covenant, new Inequality(MathOperator::INFERIOR_OR_EQUAL, '0.9'));

        foreach ($tranches as $tranche) {
            $marginImpact = new MarginImpact($marginRule, $tranche, '0.9');
            $marginRule->addImpact($marginImpact);
        }

        return $marginRule;
    }
}
