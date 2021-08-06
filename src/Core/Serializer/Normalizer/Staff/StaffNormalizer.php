<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\Staff;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Core\Entity\Staff;

class StaffNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    private const ALREADY_CALLED = 'STAFF_NORMALIZER_ALREADY_CALLED';

    private NormalizerInterface $normalizer;

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Staff && !isset($context[static::ALREADY_CALLED]);
    }

    public function setNormalizer(NormalizerInterface $normalizer): StaffNormalizer
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $currentUser = $this->security->getUser();

        if ($currentUser === $object->getUser()) {
            $context[AbstractNormalizer::GROUPS]   = $context[AbstractNormalizer::GROUPS] ?? [];
            $context[AbstractNormalizer::GROUPS][] = Staff::SERIALIZER_GROUP_OWNER_READ;
        }

        return $this->normalizer->normalize($object, $format, $context);
    }
}
