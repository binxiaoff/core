<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Luhn;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\SequentiallyValidator;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @Annotation
 */
class Siren extends Sequentially
{
    public string $message = 'Invalid Siren.';

    /**
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct([
            'constraints' => [
                new Length(['max' => 9, 'min' => 9, 'exactMessage' => $options['message'] ?? $this->message]),
                new Type(['type' => 'digit', 'message' => $options['message'] ?? $this->message]),
                new Luhn(['message' => $options['message'] ?? $this->message]),
            ],
        ]);
    }

    public function validatedBy(): string
    {
        return SequentiallyValidator::class;
    }
}
