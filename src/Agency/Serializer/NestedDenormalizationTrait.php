<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

trait NestedDenormalizationTrait
{
    use DenormalizerAwareTrait;

    /**
     * @param                       $data
     * @param string      $type
     * @param string|null $format
     * @param array       $context
     * @param array       $nestedProperties
     *
     * @return mixed
     *
     * @throws ExceptionInterface
     */
    private function nestedDenormalize($data, string $type, ?string $format = null, array $context = [], $nestedProperties = [])
    {
        $denormalized = $this->denormalizer->denormalize(array_diff_key($data, array_flip($nestedProperties)), $type, $format, $context);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $denormalized;

        $context = $this->updateContextBeforeSecondDenormalization($denormalized, $context);

        $nestedData = array_filter(array_intersect_key($data, array_flip($nestedProperties)));

        if ($nestedData) {
            $denormalized = $this->denormalizer->denormalize($nestedData, $type, $format, $context);
        }

        return $denormalized;
    }

    /**
     * @param $denormalized
     * @param array $context
     *
     * @return array
     */
    abstract protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array;
}
