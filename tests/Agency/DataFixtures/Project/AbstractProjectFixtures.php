<?php

declare(strict_types=1);

namespace KLS\Test\Agency\DataFixtures\Project;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Exception;
use KLS\Agency\Entity\Borrower;
use KLS\Agency\Entity\Covenant;
use KLS\Agency\Entity\Participation;
use KLS\Agency\Entity\Project;
use KLS\Agency\Entity\Tranche;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\Tranche\LoanType;
use KLS\Core\Entity\Constant\Tranche\RepaymentType;
use KLS\Core\Entity\Embeddable\LendingRate;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

abstract class AbstractProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function createTestBorrower(Project $project): Borrower
    {
        return new Borrower(
            $project,
            'Michelin',
            LegalForm::SARL,
            new NullableMoney('eur', '40000000'),
            '50, rue de la Boetie 75008 Paris',
            '514919844'
        );
    }

    public function createTestTranche(Project $project): Tranche
    {
        return new Tranche(
            $project,
            'tranche',
            '#FFFFFF',
            LoanType::TERM_LOAN,
            RepaymentType::ATYPICAL,
            40,
            new Money('eur', '30000000'),
            new LendingRate()
        );
    }

    /**
     * @throws Exception
     */
    public function createTestCovenant(Project $project): Covenant
    {
        return new Covenant(
            $project,
            'covenant',
            Covenant::NATURE_CONTROL,
            new \DateTimeImmutable('- 2 years'),
            40,
            new \DateTimeImmutable('+ 3 years')
        );
    }

    public function createTestPrimaryParticipation(Project $project, Company $participant): Participation
    {
        return new Participation(
            $project->getPrimaryParticipationPool(),
            $participant
        );
    }

    public function createTestSecondaryParticipation(Project $project, Company $participant): Participation
    {
        return new Participation(
            $project->getSecondaryParticipationPool(),
            $participant
        );
    }

    public function publishProject(Project $project)
    {
        $project->setCurrentStatus(Project::STATUS_PUBLISHED);
    }
}
