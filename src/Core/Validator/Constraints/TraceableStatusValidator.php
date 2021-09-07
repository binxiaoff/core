<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints;

use KLS\Core\Entity\Interfaces\StatusInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TraceableStatusValidator
{
    /**
     * @param $payload
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

            if (isset($payload['allowedStatus'][$lastStatusValue]) && false === \in_array($object->getStatus(), $payload['allowedStatus'][$lastStatusValue], true)) {
                $context->buildViolation('Core.StatusInterface.invalid')
                    ->setInvalidValue($object->getStatus())
                    ->atPath($path)
                    ->addViolation()
                ;
            }
        }
    }
}
