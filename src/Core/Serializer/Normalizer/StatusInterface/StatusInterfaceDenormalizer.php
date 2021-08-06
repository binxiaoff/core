<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\StatusInterface;

use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Core\Entity\Interfaces\StatusInterface;

class StatusInterfaceDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'STATUS_INTERFACE_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    public function denormalize($data, $type, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var StatusInterface $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        $previousStatus = $denormalized->getAttachedObject()->getCurrentStatus();

        if (null === $previousStatus) {
            return $denormalized;
        }

        return $previousStatus->getStatus() === $denormalized->getStatus() ? $previousStatus : $denormalized;
    }

    public function supportsDenormalization($data, $type, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && \is_subclass_of($type, StatusInterface::class);
    }
}
