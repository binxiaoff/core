<?php

declare(strict_types=1);

namespace KLS\Agency\Serializer\Normalizer;

use KLS\Agency\Entity\MarginImpact;
use KLS\Agency\Entity\MarginRule;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;

class MarginRuleNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NestedDenormalizationTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED_DENORMALIZER';

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && MarginRule::class === $type;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        return $this->nestedDenormalize($data, $type, $format, $context, ['impacts']);
    }

    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][MarginImpact::class]['rule'] = $denormalized;

        return $context;
    }
}
