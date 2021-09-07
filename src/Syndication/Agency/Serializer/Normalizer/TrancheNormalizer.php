<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Serializer\Normalizer;

use KLS\Syndication\Agency\Entity\BorrowerTrancheShare;
use KLS\Syndication\Agency\Entity\ParticipationTrancheAllocation;
use KLS\Syndication\Agency\Entity\Tranche;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;

class TrancheNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NestedDenormalizationTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return Tranche::class === $type && false === isset($context[static::ALREADY_CALLED]);
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        return $this->nestedDenormalize($data, $type, $format, $context, ['allocations', 'borrowerShares']);
    }

    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][BorrowerTrancheShare::class]['tranche']           = $denormalized;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ParticipationTrancheAllocation::class]['tranche'] = $denormalized;

        return $context;
    }
}
