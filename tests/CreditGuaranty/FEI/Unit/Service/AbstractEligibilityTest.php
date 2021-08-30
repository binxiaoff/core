<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use DateTimeImmutable;
use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use PHPUnit\Framework\TestCase;

abstract class AbstractEligibilityTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function createReservation(): Reservation
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        $program = new Program(
            'Program',
            new CompanyGroupTag(new CompanyGroup('Company Group'), 'code'),
            new Money('EUR', '42'),
            new Staff(new User('user@mail.com'), $team)
        );

        return new Reservation($program, new Staff(new User('user@mail.com'), $team));
    }

    protected function withBorrower(Reservation $reservation): void
    {
        $program              = $reservation->getProgram();
        $borrowerTypeField    = new Field('borrower_type', 'test', 'list', 'borrower', 'borrowerType', Borrower::class, false, null, null);
        $legalFormField       = new Field('legal_form', 'test', 'list', 'borrower', 'legalForm', Borrower::class, false, null, null);
        $activityCountryField = new Field('activity_country', 'test', 'list', 'borrower', 'addressCountry', Borrower::class, false, null, ['FR']);

        $reservation->getBorrower()
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType(new ProgramChoiceOption($program, 'borrower type', $borrowerTypeField))
            ->setYoungFarmer(true)
            ->setCreationInProgress(false)
            ->setSubsidiary(true)
            ->setCompanyName('Borrower Company')
            ->setActivityStartDate(new DateTimeImmutable())
            ->setAddressStreet('42 rue de de la paix')
            ->setAddressCity('Paris')
            ->setAddressPostCode('75042')
            ->setAddressDepartment('Ile-De-France')
            ->setAddressCountry(new ProgramChoiceOption($program, 'FR', $activityCountryField))
            ->setSiret(\str_repeat('1', 14))
            ->setLegalForm(new ProgramChoiceOption($program, 'legal form', $legalFormField))
            ->setEmployeesNumber(42)
            ->setTurnover(new NullableMoney('EUR', '128'))
            ->setTotalAssets(new NullableMoney('EUR', '2048'))
            ->setGrade('D')
        ;
    }

    protected function withProject(Reservation $reservation): void
    {
        $program                 = $reservation->getProgram();
        $investmentThematicField = new Field('investment_thematic', 'test', 'list', 'project', 'investmentThematic', Project::class, false, null, null);
        $investmentTypeField     = new Field('investment_type', 'test', 'list', 'project', 'investmentType', Project::class, false, null, null);
        $aidIntensityField       = new Field('aid_intensity', 'test', 'list', 'project', 'aidIntensity', Project::class, false, null, null);
        $additionalGuaranty      = new Field('additional_guaranty', 'test', 'list', 'project', 'additionalGuaranty', Project::class, false, null, null);
        $agriculturalBranch      = new Field('agricultural_branch', 'test', 'list', 'project', 'agriculturalBranch', Project::class, false, null, null);

        $reservation->getProject()
            ->setInvestmentThematic(new ProgramChoiceOption($program, 'investment thematic', $investmentThematicField))
            ->setInvestmentType(new ProgramChoiceOption($program, 'investment type', $investmentTypeField))
            ->setAidIntensity(new ProgramChoiceOption($program, '0.42', $aidIntensityField))
            ->setAdditionalGuaranty(new ProgramChoiceOption($program, 'additional guaranty', $additionalGuaranty))
            ->setAgriculturalBranch(new ProgramChoiceOption($program, 'agricultural branch', $agriculturalBranch))
            ->setFundingMoney(new NullableMoney('EUR', '42'))
        ;
    }

    protected function createFinancingObject(Reservation $reservation, bool $supportingGenerationsRenewal): FinancingObject
    {
        $program = $reservation->getProgram();

        $financingObjectTypeField = new Field('financing_object_type', 'test', 'list', 'financingObjects', 'financingObjectType', FinancingObject::class, false, null, null);
        $loanTypeField            = new Field('loan_type', 'test', 'list', 'financingObjects', 'loanType', FinancingObject::class, false, null, ['loan type 1', 'loan type 2']);

        return (new FinancingObject($reservation, new Money('EUR', '42'), false))
            ->setSupportingGenerationsRenewal($supportingGenerationsRenewal)
            ->setFinancingObjectType(new ProgramChoiceOption($program, 'financing object test', $financingObjectTypeField))
            ->setLoanType(new ProgramChoiceOption($program, 'loan type 2', $loanTypeField))
            ->setLoanDuration(4)
            ->setLoanDeferral(1)
        ;
    }
}
