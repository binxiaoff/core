<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Interfaces\StatusInterface;

class TraceableStatusValidator
{
    /**
     * @param StatusInterface           $object
     * @param ExecutionContextInterface $context
     * @param                           $payload
     */
    public static function validate(StatusInterface $object, ExecutionContextInterface $context, $payload): void
    {
        /** @var StatusInterface $lastStatus */
        $lastStatus = $object->getAttachedObject()->getStatuses()->last();
        $path       = $payload['path'] ?? 'status';
        // We check the value only if it has previous status and only when we are adding a new status...
        if ($lastStatus && null === $object->getId()) {
            $lastStatusValue = $lastStatus->getStatus();
            if ($object->getStatus() === $lastStatusValue) {
                $context->buildViolation('Core.StatusInterface.duplicated')
                    ->setInvalidValue($object->getStatus())
                    ->atPath($path)
                    ->addViolation()
                ;
            }

            if (isset($payload['allowedStatus'][$lastStatusValue]) && false === in_array($object->getStatus(), $payload['allowedStatus'][$lastStatusValue], true)) {
                $context->buildViolation('Core.StatusInterface.invalid')
                    ->setInvalidValue($object->getStatus())
                    ->atPath($path)
                    ->addViolation()
                ;
            }
        }
    }
}
