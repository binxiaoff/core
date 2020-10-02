<?php

declare(strict_types=1);

namespace Unilend\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Entity\Interfaces\StatusInterface;

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
        // We check the value only if it has previous status and only when we are adding a new status...
        if ($lastStatus && null === $object->getId() && $object->getStatus() === $lastStatus->getStatus()) {
            $builder = $context->buildViolation('StatusInterface.duplicated');
            if (isset($payload['path'])) {
                $builder->atPath('status');
            }
            $builder->addViolation();
        }
    }
}
