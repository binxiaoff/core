<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Agency\Entity\ProjectStatus;

class ProjectStatusNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return $object->getStatus();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof ProjectStatus;
    }
}
