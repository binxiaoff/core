<?php

declare(strict_types=1);

namespace Unilend\Agency\DataFixtures;

use DateTimeImmutable;
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
        $project->setAgencyContact($agencyContact);

        $project->setPrincipalSyndicationType(SyndicationType::PRIMARY);
        $project->setPrincipalParticipationType(ParticipationType::DIRECT);

        /** @var Borrower[]|array $borrowers */
        $borrowers = array_map(fn () => $this->createBorrower($project, $staff), range(0, 3));

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

        $agentParticipation = $this->createParticipation($project, $this->getReference(CompanyFixtures::CALS));
        $agentParticipation->setResponsibilities((new Bitmask(0))->add(Participation::RESPONSIBILITY_AGENT));
        $agentParticipation->setAllocations([
            new ParticipationTrancheAllocation($agentParticipation, $tranches[0], new Money('EUR', '2000000')),
        ]);

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

        array_map([$manager, 'persist'], [
            ...$borrowers,
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
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws Exception
     *
     * @return Borrower
     */
    private function createBorrower(Project $project, Staff $staff)
    {
        $siren = $this->generateSiren();
        $city  = $this->faker->city;

        $borrower = new Borrower(
            $project,
            $staff,
            $this->faker->company,
            'SARL',
            new Money('EUR', (string) $this->faker->randomFloat(0, 100000)),
            $this->faker->address,
            implode(' ', ['RCS', mb_strtoupper($city), $this->faker->randomDigit % 2 ? 'A' : 'B', $siren]),
        );

        $borrower->setReferent(new BorrowerMember($borrower, new User($this->faker->email)));
        $borrower->setSignatory(new BorrowerMember($borrower, new User($this->faker->email)));

        return $borrower;
    }

    /**
     * @return string
     */
    private function generateSiren()
    {
        // A siren use the Luhn algorithm to validate. Its final length (number + checksum must be 9)
        // https://fr.wikipedia.org/wiki/Luhn_algorithm
        // https://fr.wikipedia.org/wiki/Syst%C3%A8me_d%27identification_du_r%C3%A9pertoire_des_entreprises#Calcul_et_validit%C3%A9_d'un_num%C3%A9ro_SIREN
        $siren    = $this->faker->randomNumber(8); // First we generate a 8 digit long number
        $siren    = mb_str_split((string) $siren); // Conversion and split into an array
        $checksum = array_map(static fn ($i, $d) => 1 === $i % 2 ? array_sum(mb_str_split((string) ($d * 2))) : $d, range(0, 7), $siren); // Double each odd index digit
        $checksum = array_sum($checksum); // Sum the resulting array
        $checksum *= 9; // Multiply it by 9
        $checksum %= 10; // Checksum is the last digit of the sum

        return implode('', [...$siren, $checksum]);
    }

    /**
     * @param Project $project
     *
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
     * @param Project $project
     * @param Company $participant
     * @param bool    $secondary
     *
     * @return Participation
     */
    private function createParticipation(Project $project, Company $participant, bool $secondary = false)
    {
        return new Participation(
            $project,
            $participant,
            new Money('EUR', (string) $this->faker->numberBetween(100000)),
            $secondary
        );
    }

    /**
     * @param Project                $project
     * @param string                 $nature
     * @param string|null            $recurrence
     * @param DateTimeImmutable|null $startDate
     *
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
     * @param Covenant $covenant
     * @param int      $year
     *
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
     * @param Covenant           $covenant
     * @param Tranche[]|iterable $tranches
     *
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
