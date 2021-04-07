<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Exception;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    ContextAwareNormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    NormalizerAwareInterface,
    NormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\CovenantRule;
use Unilend\Agency\Entity\MarginRule;

class CovenantNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    private const ALREADY_CALLED_NORMALIZER = __CLASS__ . '_ALREADY_CALLED_NORMALIZER';
    private const ALREADY_CALLED_DENORMALIZER = __CLASS__ . '_ALREADY_CALLED_DENORMALIZER';

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED_DENORMALIZER]) && $type === Covenant::class;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED_DENORMALIZER] = true;

        $publication = $data['published'] ?? false;
        unset($data['published']);

        $denormalizeData = [];
        if (array_key_exists('covenantRules', $data)) {
            $denormalizeData['covenantRules'] = $data['covenantRules'] ?? [];
            unset($data['covenantRules']);
        }

        if (array_key_exists('marginRules', $data)) {
            $denormalizeData['marginRules'] = $data['marginRules'] ?? [];
            unset($data['marginRules']);
        }

        /** @var Covenant $covenant */
        $covenant = $this->denormalizer->denormalize($data, $type, $format, $context);

        if ($publication && $covenant && (false === $covenant->isPublished())) {
            $covenant->publish();
        }

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $covenant;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][CovenantRule::class]['covenant'] = $covenant;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][MarginRule::class]['covenant'] = $covenant;

        $covenant = $this->denormalizer->denormalize($denormalizeData, $type, $format, $context);

        return $covenant;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Covenant && !isset($context[static::ALREADY_CALLED_NORMALIZER]);
    }
}
