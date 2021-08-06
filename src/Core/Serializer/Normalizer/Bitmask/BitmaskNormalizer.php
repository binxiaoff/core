<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\Bitmask;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Core\Model\Bitmask;

class BitmaskNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return $object->get();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Bitmask;
    }
}
