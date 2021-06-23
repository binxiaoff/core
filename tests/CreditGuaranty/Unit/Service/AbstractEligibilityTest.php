<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use Exception;
use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;

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
            new Money('eur', '42'),
            new Staff(new User('user@mail.com'), $team)
        );

        $reservation = new Reservation($program, new Staff(new User('user@mail.com'), $team));
        $reservation
            ->setBorrower($this->createBorrower($reservation))
            ->setProject($this->creatProject($reservation))
        ;

        return $reservation;
    }

    protected function createFinancingObject(Reservation $reservation): FinancingObject
    {
        $program = $reservation->getProgram();

        $financingObjectField = new Field('financing_object', 'test', 'list', 'financingObjects', 'financingObject', FinancingObject::class, false, null, null);
        $loanTypeField        = new Field('loan_type', 'test', 'list', 'financingObjects', 'loanType', FinancingObject::class, false, null, ['loan type 1', 'loan type 2']);

        return new FinancingObject(
            $reservation,
            new ProgramChoiceOption($program, 'financing object test', $financingObjectField),
            new ProgramChoiceOption($program, 'loan type 2', $loanTypeField),
            4,
            new Money('EUR', '42'),
            false
        );
    }

    private function createBorrower(Reservation $reservation): Borrower
    {
        $program              = $reservation->getProgram();
        $borrowerTypeField    = new Field('borrower_type', 'test', 'list', 'borrower', 'borrowerType', Borrower::class, false, null, null);
        $legalFormField       = new Field('legal_form', 'test', 'list', 'borrower', 'legalForm', Borrower::class, false, null, null);
        $activityCountryField = new Field('activity_country', 'test', 'list', 'borrower', 'addressCountry', Borrower::class, false, null, ['FR']);

        return (new Borrower($reservation, 'Borrower Company', 'D'))
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType(new ProgramChoiceOption($program, 'borrower type', $borrowerTypeField))
            ->setYoungFarmer(true)
            ->setCreationInProgress(false)
            ->setSubsidiary(true)
            ->setAddressStreet('42 rue de de la paix')
            ->setAddressCity('Paris')
            ->setAddressPostCode('75042')
            ->setAddressDepartment('Ile-De-France')
            ->setAddressCountry(new ProgramChoiceOption($program, 'FR', $activityCountryField))
            ->setSiret(str_repeat('1', 14))
            ->setLegalForm(new ProgramChoiceOption($program, 'legal form', $legalFormField))
            ->setEmployeesNumber(42)
            ->setTurnover(new NullableMoney('EUR', '128'))
            ->setTotalAssets(new NullableMoney('EUR', '2048'))
        ;
    }

    private function creatProject(Reservation $reservation): Project
    {
        $program                 = $reservation->getProgram();
        $investmentThematicField = new Field('investment_thematic', 'test', 'list', 'project', 'investmentThematic', Project::class, false, null, null);
        $investmentTypeField     = new Field('investment_type', 'test', 'list', 'project', 'investmentType', Project::class, false, null, null);
        $aidIntensityField       = new Field('aid_intensity', 'test', 'list', 'project', 'aidIntensity', Project::class, false, null, null);
        $additionalGuaranty      = new Field('additional_guaranty', 'test', 'list', 'project', 'additionalGuaranty', Project::class, false, null, null);
        $agriculturalBranch      = new Field('agricultural_branch', 'test', 'list', 'project', 'agriculturalBranch', Project::class, false, null, null);

        return (new Project($reservation, new Money('eur', '42')))
            ->setInvestmentThematic(new ProgramChoiceOption($program, 'investment thematic', $investmentThematicField))
            ->setInvestmentType(new ProgramChoiceOption($program, 'investment type', $investmentTypeField))
            ->setAidIntensity(new ProgramChoiceOption($program, '42%', $aidIntensityField))
            ->setAdditionalGuaranty(new ProgramChoiceOption($program, 'additional guaranty', $additionalGuaranty))
            ->setAgriculturalBranch(new ProgramChoiceOption($program, 'agricultural branch', $agriculturalBranch))
        ;
    }
}
