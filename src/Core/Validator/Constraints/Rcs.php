<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\SequentiallyValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Annotation
 */
class Rcs extends Sequentially
{
    /**
     * Default message. Not a constant for autocompletion.
     */
    public string $message = 'Invalid Rcs.';

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'constraints' => [
                new Regex([
                    'normalizer' => 'strtoupper',
                    'pattern'    => '/^RCS (\w|-)+ (A|B) \d{9}$/',
                    'message'    => $options['message'] ?? $this->message,
                ]),
                new Callback([__CLASS__, 'validateSiren']),
            ],
        ]);
    }

    /**
     * Separate into another method for cache.
     *
     * @param $object
     */
    public static function validateSiren($object, ExecutionContextInterface $context)
    {
        $value = $context->getValue();

        $tokens = \explode(' ', $value);

        $siren = $tokens ? \end($tokens) : null;

        $validator = $context->getValidator()->inContext($context);

        $validator->validate($siren, [new Siren()]);
    }

    public function validatedBy(): string
    {
        return SequentiallyValidator::class;
    }
}
