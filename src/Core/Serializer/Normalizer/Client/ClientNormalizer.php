<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Client;

use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Core\Entity\Clients;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class ClientNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    /**
     * @var NormalizerInterface
     */
    private NormalizerInterface $normalizer;

    private const ALREADY_CALLED = 'CLIENT_NORMALIZER_ALREADY_CALLED';

    /** @var ServiceTermsManager */
    private ServiceTermsManager $serviceTermsManager;

    /**
     * @param ServiceTermsManager $serviceTermsManager
     */
    public function __construct(ServiceTermsManager $serviceTermsManager)
    {
        $this->serviceTermsManager = $serviceTermsManager;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Clients && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param NormalizerInterface $normalizer
     *
     * @return ClientNormalizer
     */
    public function setNormalizer(NormalizerInterface $normalizer): ClientNormalizer
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @inheritDoc
     */
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
