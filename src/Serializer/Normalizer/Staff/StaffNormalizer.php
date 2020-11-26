<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Core\Entity\Staff;

class StaffNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    /**
     * @var NormalizerInterface
     */
    private NormalizerInterface $normalizer;

    private const ALREADY_CALLED = 'STAFF_NORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Staff && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param NormalizerInterface $normalizer
     *
     * @return StaffNormalizer
     */
    public function setNormalizer(NormalizerInterface $normalizer): StaffNormalizer
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $currentUser = $this->security->getUser();

        if ($currentUser === $object->getClient()) {
            $context[AbstractNormalizer::GROUPS] = $context[AbstractNormalizer::GROUPS] ?? [];
            $context[AbstractNormalizer::GROUPS][] = Staff::SERIALIZER_GROUP_OWNER_READ;
        }

        return $this->normalizer->normalize($object, $format, $context);
    }
}
