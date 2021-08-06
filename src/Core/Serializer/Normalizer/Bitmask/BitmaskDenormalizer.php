<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\Bitmask;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Unilend\Core\Model\Bitmask;

class BitmaskDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return new Bitmask($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return Bitmask::class === $type;
    }
}
