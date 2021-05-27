<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
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
use Unilend\Core\Entity\Embeddable\NullablePerson;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Model\Bitmask;

class ProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
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

        $project = new Project(
            $staff,
            $this->faker->title,
            $this->faker->name,
            new Money('EUR', '5000000'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+1 year')
        );

        $project->setCompanyGroupTag($staff->getAvailableCompanyGroupTags()[0]);

        $manager->persist($project);

        $agencyContact = (new NullablePerson())->setFirstName($this->faker->firstName)->setLastName($this->faker->lastName);
        $project->getAgent()
            ->setContact($agencyContact)
            ->setLegalForm(LegalForm::EURL)
            ->setIban($this->faker->iban())
            ->setBic('AGRIMQMX')
            ->setHeadOffice($this->faker->address)
            ->setRcs(implode(' ', ['RCS', mb_strtoupper($this->faker->city), $this->faker->randomDigit % 2 ? 'A' : 'B', $project->getAgent()->getMatriculationNumber()]))
            ->setCapital(new Money('EUR', '0'))
            ->setBankInstitution('bank institution')
        ;
        $project->getPrimaryParticipationPool()->setSyndicationType(SyndicationType::PRIMARY);
        $project->getPrimaryParticipationPool()->setParticipationType(ParticipationType::DIRECT);

        /** @var Borrower[]|array $borrowers */
        $borrowers = array_map(fn () => $this->createBorrower($project, $staff), range(0, 3));

        $borrowerMembers = [];
        foreach ($borrowers as $borrower) {
            $borrowerMembers[] = $this->createBorrowerMember($borrower, true);
            $borrowerMembers[] = $this->createBorrowerMember($borrower, false);
        }

        /** @var Tranche[]|array $tranches */
        $tranches = array_map(fn () => $this->createTranche($project), range(0, 2));

        $borrowerTrancheShares = [
            new BorrowerTrancheShare($borrowers[0], $tranches[0], new Money('EUR', '2000000')),
            new BorrowerTrancheShare($borrowers[1], $tranches[0], new Money('EUR', '2000000')),
            new BorrowerTrancheShare($borrowers[2], $tranches[0], new Money('EUR', '2000000')),
            new BorrowerTrancheShare($borrowers[1], $tranches[1], new Money('EUR', '2000000')),
            new BorrowerTrancheShare($borrowers[2], $tranches[2], new Money('EUR', '2000000')),
        ];

        $financialCovenant = $this->createCovenant($project, Covenant::NATURE_FINANCIAL_ELEMENT, Covenant::RECURRENCE_12M);

        $covenantRules = array_map(
            fn ($index) => $this->createCovenantRule($financialCovenant, $index),
            range($financialCovenant->getStartYear(), $financialCovenant->getEndYear())
        );

        $covenants = [
            $this->createCovenant($project, Covenant::NATURE_DOCUMENT, Covenant::RECURRENCE_3M),
            $this->createCovenant($project, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M),
            $this->createCovenant($project, Covenant::NATURE_DOCUMENT, Covenant::RECURRENCE_3M, new DateTimeImmutable('-3 years')),
            $this->createCovenant($project, Covenant::NATURE_CONTROL, Covenant::RECURRENCE_3M, new DateTimeImmutable('-3 years')),
            $financialCovenant,
        ];

        $covenants[2]->publish();
        $covenants[3]->publish();

        $marginRule = $this->createMarginRule($financialCovenant, $tranches);

        $agentParticipation = $project->getAgentParticipation();
        $agentParticipation->setResponsibilities((new Bitmask(0))->add(Participation::RESPONSIBILITY_AGENT));
        $agentParticipation->addAllocation(new ParticipationTrancheAllocation($agentParticipation, $tranches[0], new Money('EUR', '2000000')));

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

        /** @var Participation $participation */
        foreach ($participations as $participation) {
            $participation->setAllocations(new ArrayCollection([new ParticipationTrancheAllocation($participation, $tranches[0], new Money('EUR', '20000'))]));
        }

        array_map([$manager, 'persist'], [
            ...$borrowers,
            ...$borrowerMembers,
            ...$tranches,
            ...$borrowerTrancheShares,
            ...$participations,
            ...$covenants,
            ...$covenantRules,
            $marginRule,
        ]);

        $manager->flush();
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
    private function createBorrower(Project $project): Borrower
    {
        return new Borrower(
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
    }

    private function createBorrowerMember(Borrower $borrower, bool $referentOrSignatory): BorrowerMember
    {
        $borrowerMember = new BorrowerMember($borrower, new User($this->faker->email));
        if ($referentOrSignatory) {
            $borrowerMember->setReferent(true);
        } else {
            $borrowerMember->setSignatory(true);
        }

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
