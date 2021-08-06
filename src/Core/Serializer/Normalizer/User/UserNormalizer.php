<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\User;

use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Service\ServiceTerms\ServiceTermsManager;

class UserNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    private const ALREADY_CALLED = 'USER_NORMALIZER_ALREADY_CALLED';

    private NormalizerInterface $normalizer;

    private ServiceTermsManager $serviceTermsManager;

    public function __construct(ServiceTermsManager $serviceTermsManager)
    {
        $this->serviceTermsManager = $serviceTermsManager;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof User && !isset($context[static::ALREADY_CALLED]);
    }

    public function setNormalizer(NormalizerInterface $normalizer): UserNormalizer
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $currentServiceTerms = $this->serviceTermsManager->getCurrentVersion();
        if (false === $this->serviceTermsManager->hasAccepted($object, $currentServiceTerms)) {
            $object->setServiceTermsToSign($currentServiceTerms);
        }

        return $this->normalizer->normalize($object, $format, $context);
    }
}
