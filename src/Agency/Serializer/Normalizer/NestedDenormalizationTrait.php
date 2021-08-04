<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

trait NestedDenormalizationTrait
{
    use DenormalizerAwareTrait;

    /**
     * @param $denormalized
     */
    abstract protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array;

    /**
     * @param mixed $data
     *
     * @throws ExceptionInterface
     *
     * @return mixed
     */
    private function nestedDenormalize($data, string $type, ?string $format = null, array $context = [], array $nestedProperties = [])
    {
        $denormalized = $this->denormalizer->denormalize(\array_diff_key($data, \array_flip($nestedProperties)), $type, $format, $context);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $denormalized;

        $context = $this->updateContextBeforeSecondDenormalization($denormalized, $context);

        $nestedData = \array_intersect_key($data, \array_flip($nestedProperties));

        if ($nestedData) {
            $denormalized = $this->denormalizer->denormalize($nestedData, $type, $format, $context);
        }

        return $denormalized;
    }
}
