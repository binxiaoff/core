<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Embeddable\Address;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\NafNace;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\BorrowerBusinessActivity;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;

abstract class AbstractEligibilityTest extends KernelTestCase
{
    protected function createReservation(): Reservation
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        $program = new Program(
            'Program',
            new CompanyGroupTag(new CompanyGroup('Company Group'), 'code'),
            new Money('eur', '42'),
            new Staff(new User('user@mail.com'), $team)
        );

        return (new Reservation(
            $program,
            $this->createBorrower($program),
            new Staff(new User('user@mail.com'), $team)
        ))
            ->setBorrowerBusinessActivity($this->createBorrowerBusinessActivity())
            ->setProject($this->creatProject($program))
        ;
    }

    protected function createFinancingObject(Reservation $reservation): FinancingObject
    {
        $program = $reservation->getProgram();

        $financingObjectField = new Field('financing_object', 'test', 'list', 'financingObjects::financingObject', false, null, null);
        $loanTypeField        = new Field('loan_type', 'test', 'list', 'financingObjects::loanType', false, null, ['loan type 1', 'loan type 2', 'loan type 3']);

        return new FinancingObject(
            $reservation,
            new ProgramChoiceOption($program, 'financing object test', $financingObjectField),
            new ProgramChoiceOption($program, 'loan type 2', $loanTypeField),
            4,
            new Money('EUR', '42'),
            false
        );
    }

    private function createBorrower(Program $program): Borrower
    {
        $borrowerTypeField = new Field('borrower_type', 'test', 'list', 'borrower::borrowerType', false, null, null);
        $legalFormField    = new Field('legal_form', 'test', 'list', 'borrower::legalForm', false, null, null);

        return (new Borrower('Borrower Company', 'D'))
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType(new ProgramChoiceOption($program, 'borrower type', $borrowerTypeField))
            ->setLegalForm(new ProgramChoiceOption($program, 'legal form', $legalFormField))
            ->setCreationInProgress(false)
            ->setAddress((new Address())->setCountry('USA'))
        ;
    }

    private function createBorrowerBusinessActivity(): BorrowerBusinessActivity
    {
        return (new BorrowerBusinessActivity())
            ->setAddress((new Address())->setCountry('FR'))
            ->setSiret(str_repeat('1', 14))
            ->setSubsidiary(true)
            ->setGrant(new NullableMoney('EUR', '42'))
            ->setEmployeesNumber(42)
            ->setLastYearTurnover(new NullableMoney('EUR', '128'))
            ->setFiveYearsAverageTurnover(new NullableMoney('EUR', '100'))
            ->setTotalAssets(new NullableMoney('EUR', '2048'))
        ;
    }

    private function creatProject(Program $program): Project
    {
        $investmentThematicField = new Field('investment_thematic', 'test', 'list', 'project::investmentThematic', false, null, null);
        $projectNafCodeField     = new Field('project_naf_code', 'test', 'list', 'project::projectNafCode', false, null, null);

        return new Project(
            new Money('eur', '42'),
            new ProgramChoiceOption($program, 'investment thematic', $investmentThematicField),
            new ProgramChoiceOption($program, 'N42', $projectNafCodeField),
        );
    }
}
