<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Unilend\Agency\Entity\Borrower;
use Unilend\Agency\Entity\BorrowerMember;

class BorrowerNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NestedDenormalizationTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return Borrower::class === $type && false === isset($context[static::ALREADY_CALLED]);
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $denormalized = $this->nestedDenormalize($data, $type, $format, $context, ['members']);

        return $denormalized;
    }

    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][BorrowerMember::class]['borrower'] = $denormalized;

        return $context;
    }
}
