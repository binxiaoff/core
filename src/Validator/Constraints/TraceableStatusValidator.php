<?php

declare(strict_types=1);

namespace Unilend\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\ProjectParticipationStatus;

class TraceableStatusValidator
{
    /**
     * @param StatusInterface           $object
     * @param ExecutionContextInterface $context
     * @param                           $payload
     */
    public static function validate(StatusInterface $object, ExecutionContextInterface $context, $payload): void
    {
        $lastStatus = $object->getAttachedObject()->getStatuses()->last();
        // We check the value only if it has previous status and only when we are adding a new status...
        if ($lastStatus && null === $object->getId()) {
            if ($object->getStatus() === $lastStatus->getStatus()) {
                $context->buildViolation('ProjectParticipation.status.duplicated')->atPath('status')->addViolation();
            }

            if (ProjectParticipationStatus::STATUS_ACTIVE !== $lastStatus->getStatus()) {
                $context->buildViolation('ProjectParticipation.status.unchangeable')->atPath('status')->addViolation();
            }
        }
    }
}
