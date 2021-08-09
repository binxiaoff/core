<?php

declare(strict_types=1);

namespace KLS\Core\Serializer\Normalizer\Bitmask;

use KLS\Core\Model\Bitmask;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BitmaskNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = [])
    {
        return $object->get();
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Bitmask;
    }
}
