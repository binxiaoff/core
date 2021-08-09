<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Validator\Constraints;

use KLS\CreditGuaranty\Entity\Borrower;
use KLS\CreditGuaranty\Entity\Project;
use KLS\CreditGuaranty\Entity\ReservationStatus;
use KLS\CreditGuaranty\Repository\ProgramEligibilityRepository;
use KLS\CreditGuaranty\Service\EligibilityChecker;
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

        if (false === ($reservation->getBorrower() instanceof Borrower)) {
            $this->context->buildViolation('CreditGuaranty.Reservation.borrower.required')
                ->atPath('reservation.borrower')
                ->addViolation()
            ;
        }

        $project = $reservation->getProject();

        if (false === ($project instanceof Project)) {
            $this->context->buildViolation('CreditGuaranty.Reservation.project.required')
                ->atPath('reservation.project')
                ->addViolation()
            ;
        }

        if (0 === $reservation->getFinancingObjects()->count()) {
            $this->context->buildViolation('CreditGuaranty.Reservation.financingObject.required')
                ->atPath('reservation.financingObjects')
                ->addViolation()
            ;
        }

        if ($project->isActivateEsbCalculation()) {
            if (false === $reservation->isGrossSubsidyEquivalentEligible()) {
                $this->context->buildViolation('CreditGuaranty.Reservation.esb.ineligible')
                    ->atPath('reservation')
                    ->addViolation()
                ;
            }
        }

        foreach ($this->programEligibilityRepository->findFieldCategoriesByProgram($reservation->getProgram()) as $category) {
            $ineligibles = $this->eligibilityChecker->check($reservation, true, $category);

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
