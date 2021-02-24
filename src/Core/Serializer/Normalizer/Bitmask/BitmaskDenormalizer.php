<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\Bitmask;

use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Unilend\Core\Model\Bitmask;

class BitmaskDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (false === is_numeric($data)) {
            throw new InvalidArgumentException();
        }

        return new Bitmask((int) $data);
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === Bitmask::class;
    }
}
