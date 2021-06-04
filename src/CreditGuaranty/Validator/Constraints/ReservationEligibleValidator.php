<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Unilend\CreditGuaranty\Entity\BorrowerBusinessActivity;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Service\EligibilityChecker;

class ReservationEligibleValidator extends ConstraintValidator
{
    private const FIELD_CATEGORIES = ['general', 'profile', 'activity', 'project', 'loan'];

    private EligibilityChecker $eligibilityChecker;

    public function __construct(EligibilityChecker $eligibilityChecker)
    {
        $this->eligibilityChecker = $eligibilityChecker;
    }

    /**
     * @param ReservationStatus   $value
     * @param ReservationEligible $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (ReservationStatus::STATUS_SENT !== $value->getStatus()) {
            return;
        }

        $reservation = $value->getReservation();

        if (false === ($reservation->getBorrowerBusinessActivity() instanceof BorrowerBusinessActivity)) {
            $this->context->buildViolation('CreditGuaranty.Reservation.borrowerBusinessActivity.missing')
                ->atPath('reservation.borrowerBusinessActivity')
                ->addViolation()
            ;

            return;
        }

        // no need to check project because it is done in ReservationStatus

        if (0 === $reservation->getFinancingObjects()->count()) {
            $this->context->buildViolation('CreditGuaranty.Reservation.financingObject.missing')
                ->atPath('reservation.financingObjects')
                ->addViolation()
            ;

            return;
        }

        foreach (self::FIELD_CATEGORIES as $category) {
            if (false === $this->eligibilityChecker->check($reservation, $category)) {
                $this->context->buildViolation('CreditGuaranty.Reservation.ineligibleCategory')
                    ->setParameter('{{ category }}', $category)
                    ->atPath('reservation')
                    ->addViolation()
                ;
            }
        }
    }
}
