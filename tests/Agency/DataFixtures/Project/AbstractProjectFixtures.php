<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\DataFixtures\Project;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Exception;
use Unilend\Agency\Entity\Borrower;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Tranche;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Constant\LegalForm;
use Unilend\Core\Entity\Constant\Tranche\LoanType;
use Unilend\Core\Entity\Constant\Tranche\RepaymentType;
use Unilend\Core\Entity\Embeddable\LendingRate;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

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
