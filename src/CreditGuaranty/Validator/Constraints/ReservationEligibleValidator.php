<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;
use Unilend\CreditGuaranty\Service\EligibilityChecker;

class ReservationEligibleValidator extends ConstraintValidator
{
    private ProgramEligibilityRepository $programEligibilityRepository;
    private EligibilityChecker $eligibilityChecker;

    public function __construct(ProgramEligibilityRepository $programEligibilityRepository, EligibilityChecker $eligibilityChecker)
    {
        $this->programEligibilityRepository = $programEligibilityRepository;
        $this->eligibilityChecker           = $eligibilityChecker;
    }

    /**
     * @param ReservationStatus   $value
     * @param ReservationEligible $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (ReservationStatus::STATUS_SENT !== $value->getStatus() || null !== $value->getId()) {
            return;
        }

        $reservation = $value->getReservation();

        if (false === ($reservation->getBorrower() instanceof Borrower)) {
            $this->context->buildViolation('CreditGuaranty.Reservation.borrower.missing')
                ->atPath('reservation.borrower')
                ->addViolation()
            ;

            return;
        }

        if (false === ($reservation->getProject() instanceof Project)) {
            $this->context->buildViolation('CreditGuaranty.Reservation.project.missing')
                ->atPath('reservation.project')
                ->addViolation()
            ;

            return;
        }

        if (0 === $reservation->getFinancingObjects()->count()) {
            $this->context->buildViolation('CreditGuaranty.Reservation.financingObject.missing')
                ->atPath('reservation.financingObjects')
                ->addViolation()
            ;

            return;
        }

        foreach ($this->programEligibilityRepository->findFieldCategoriesByProgram($reservation->getProgram()) as $category) {
            $ineligibles = $this->eligibilityChecker->check($reservation, true, $category);

            if (false === empty($ineligibles)) {
                foreach ($ineligibles as $category => $fieldAliases) {
                    $this->context->buildViolation('CreditGuaranty.Reservation.ineligibles')
                        ->setParameter('{{ fieldAliases }}', implode(', ', $fieldAliases))
                        ->setParameter('{{ category }}', $category)
                        ->atPath('reservation')
                        ->addViolation()
                    ;
                }
            }
        }
    }
}
