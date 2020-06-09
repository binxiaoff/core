<?php

declare(strict_types=1);

namespace Unilend\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Interfaces\StatusInterface;

class TraceableStatusValidator
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param StatusInterface           $object
     * @param ExecutionContextInterface $context
     * @param                           $payload
     */
    public function validate(StatusInterface $object, ExecutionContextInterface $context, $payload): void
    {
        $lastStatus = $object->getAttachedObject()->getStatuses()->last();
        if ($lastStatus && $object->getStatus() === $lastStatus->getStatus()) {
            $message = $this->translator->trans('status.duplicated-status');
            $context->buildViolation($message)->addViolation();
        }
    }
}
