<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\CovenantRule;
use Unilend\Agency\Entity\MarginRule;

class CovenantNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    use ObjectToPopulateTrait;
    use NestedDenormalizationTrait;
    use NormalizerAwareTrait;

    private const ALREADY_CALLED_NORMALIZER   = __CLASS__ . '_ALREADY_CALLED_NORMALIZER';
    private const ALREADY_CALLED_DENORMALIZER = __CLASS__ . '_ALREADY_CALLED_DENORMALIZER';

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED_DENORMALIZER]) && Covenant::class === $type;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED_DENORMALIZER] = true;

        $publication = $data['published'] ?? false;
        unset($data['published']);

        $denormalized = $this->nestedDenormalize($data, $type, $format, $context, ['covenantRules', 'marginRules']);

        if ($publication && $denormalized && (false === $denormalized->isPublished())) {
            $denormalized->publish();
        }

        return $denormalized;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED_NORMALIZER] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        // Enforce array for association field with indexBy attribute
        if (array_key_exists('covenantRules', $data)) {
            $data['covenantRules'] = array_values($data['covenantRules']);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Covenant && !isset($context[static::ALREADY_CALLED_NORMALIZER]);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][CovenantRule::class]['covenant'] = $denormalized;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][MarginRule::class]['covenant']   = $denormalized;

        return $context;
    }
}
