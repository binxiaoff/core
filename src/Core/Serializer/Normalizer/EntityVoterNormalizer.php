<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityVoterNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var ResourceClassResolverInterface
     */
    private ResourceClassResolverInterface $resourceClassResolver;

    /**
     * @param Security                       $security
     * @param ResourceClassResolverInterface $resourceClassResolver
     */
    public function __construct(Security $security, ResourceClassResolverInterface $resourceClassResolver)
    {
        $this->security = $security;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return false === isset($context[static::ALREADY_CALLED]) &&
            \is_object($data) &&
            $this->resourceClassResolver->isResourceClass(\get_class($data));
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        /** @var array $normalized */
        $normalized = $this->normalizer->normalize($object, $format, $context);

        if (\is_array($normalized)) {
            $normalized['permissions'] = [
                'edit' => $this->security->isGranted('edit', $object),
                'delete' => $this->security->isGranted('delete', $object),
            ];
        }

        return $normalized;
    }
}
