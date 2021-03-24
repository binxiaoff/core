<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationTrancheAllocation;

class ParticipationNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return Participation::class === $type && false === isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param $data
     * @param string      $type
     * @param string|null $format
     * @param array       $context
     *
     * @return Participation
     *
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $nestedProperties = ['allocations'];

        /** @var Participation $denormalized */
        $denormalized = $this->denormalizer->denormalize(array_diff_key($data, array_flip($nestedProperties)), $type, $format, $context);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $denormalized;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ParticipationTrancheAllocation::class]['participation'] = $denormalized;

        $denormalized = $this->denormalizer->denormalize(array_intersect_key($data, array_flip($nestedProperties)), $type, $format, $context);

        return $denormalized;
    }
}
