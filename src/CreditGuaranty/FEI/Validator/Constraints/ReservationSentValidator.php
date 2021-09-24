<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use KLS\CreditGuaranty\FEI\Service\EligibilityChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ReservationSentValidator extends ConstraintValidator
{
    private ProgramEligibilityRepository $programEligibilityRepository;
    private EligibilityChecker $eligibilityChecker;

    public function __construct(ProgramEligibilityRepository $programEligibilityRepository, EligibilityChecker $eligibilityChecker)
    {
        $this->programEligibilityRepository = $programEligibilityRepository;
        $this->eligibilityChecker           = $eligibilityChecker;
    }

    /**
     * @param ReservationStatus $value
     * @param ReservationSent   $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (ReservationStatus::STATUS_SENT !== $value->getStatus() || null !== $value->getId()) {
            return;
        }

        $reservation = $value->getReservation();
        $program     = $reservation->getProgram();

        if (0 === $reservation->getFinancingObjects()->count()) {
            $this->context->buildViolation('CreditGuaranty.Reservation.financingObject.required')
                ->atPath('reservation.financingObjects')
                ->addViolation()
            ;

            return;
        }

        if (true === $program->isEsbCalculationActivated()) {
            foreach ($reservation->getFinancingObjects() as $financingObject) {
                if (null === $financingObject->getLoanDuration()) {
                    $this->context->buildViolation('CreditGuaranty.Reservation.financingObject.loanDuration.requiredForEsb')
                        ->atPath('reservation.financingObjects')
                        ->addViolation()
                    ;
                }
            }

            $project = $reservation->getProject();

            if (false === ($project->getAidIntensity() instanceof ProgramChoiceOption)) {
                $this->context->buildViolation('CreditGuaranty.Reservation.project.aidIntensity.requiredForEsb')
                    ->atPath('reservation.project.aidIntensity')
                    ->addViolation()
                ;
            }

            if ($project->getTotalFeiCredit()->isNull()) {
                $this->context->buildViolation('CreditGuaranty.Reservation.project.totalFeiCredit.requiredForEsb')
                    ->atPath('reservation.project.totalFeiCredit')
                    ->addViolation()
                ;
            }

            if ($project->getGrant()->isNull()) {
                $this->context->buildViolation('CreditGuaranty.Reservation.project.grant.requiredForEsb')
                    ->atPath('reservation.project.grant')
                    ->addViolation()
                ;
            }

            if (false === $reservation->isGrossSubsidyEquivalentEligible()) {
                $this->context->buildViolation('CreditGuaranty.Reservation.esb.ineligible')
                    ->atPath('reservation')
                    ->addViolation()
                ;
            }
        }

        foreach ($this->programEligibilityRepository->findFieldCategoriesByProgram($program) as $fieldCategory) {
            $ineligibles = $this->eligibilityChecker->check($reservation, true, $fieldCategory);

            if (false === empty($ineligibles)) {
                foreach ($ineligibles as $category => $fieldAliases) {
                    $this->context->buildViolation('CreditGuaranty.Reservation.category.ineligibles')
                        ->setParameter('{{ fieldAliases }}', \implode(', ', $fieldAliases))
                        ->setParameter('{{ category }}', $category)
                        ->atPath('reservation')
                        ->addViolation()
                    ;
                }
            }
        }
    }
}
