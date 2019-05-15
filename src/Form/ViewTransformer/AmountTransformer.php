<?php

declare(strict_types=1);

namespace Unilend\Form\ViewTransformer;

use NumberFormatter;
use Symfony\Component\Form\DataTransformerInterface;

class AmountTransformer implements DataTransformerInterface
{
    /** @var NumberFormatter */
    private $numberFormatter;

    /**
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($amount)
    {
        return $amount ?? $this->numberFormatter->format($amount);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($amount)
    {
        return preg_replace('/[^0-9]/', '', $amount);
    }
}
