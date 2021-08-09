<?php

declare(strict_types=1);

namespace KLS\Agency\Serializer\Normalizer;

use Exception;
use KLS\Agency\Entity\Covenant;
use KLS\Agency\Entity\CovenantRule;
use KLS\Agency\Entity\MarginRule;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class CovenantNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    use ObjectToPopulateTrait;
    use NestedDenormalizationTrait;
    use NormalizerAwareTrait;

    private const ALREADY_CALLED_NORMALIZER   = __CLASS__ . '_ALREADY_CALLED_NORMALIZER';
    private const ALREADY_CALLED_DENORMALIZER = __CLASS__ . '_ALREADY_CALLED_DENORMALIZER';

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED_DENORMALIZER]) && Covenant::class === $type;
    }

    /**
     * @param mixed $data
     *
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED_DENORMALIZER] = true;

        $publication = $data['published'] ?? false;
        unset($data['published']);

        /** @var Covenant $denormalized */
        $denormalized = $this->nestedDenormalize($data, $type, $format, $context, ['covenantRules', 'marginRules']);

        if ($publication && $denormalized && (false === $denormalized->isPublished()) && $denormalized->getProject()->isPublished()) {
            $denormalized->publish();
        }

        return $denormalized;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED_NORMALIZER] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        // Enforce array for association field with indexBy attribute
        if (\array_key_exists('covenantRules', $data)) {
            $data['covenantRules'] = \array_values($data['covenantRules']);
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Covenant && !isset($context[static::ALREADY_CALLED_NORMALIZER]);
    }

    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][CovenantRule::class]['covenant'] = $denormalized;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][MarginRule::class]['covenant']   = $denormalized;

        return $context;
    }
}
