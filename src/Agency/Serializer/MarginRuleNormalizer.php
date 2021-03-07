<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\MarginImpact;
use Unilend\Agency\Entity\MarginRule;

class MarginRuleNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED_DENORMALIZER = __CLASS__ . '_ALREADY_CALLED_DENORMALIZER';

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED_DENORMALIZER]) && $type === MarginRule::class;
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED_DENORMALIZER] = true;

        $marginImpacts = $data['impacts'] ?? [];
        unset($data['impacts']);

        /** @var Covenant $covenant */
        $marginRule = $this->denormalizer->denormalize($data, $type, $format, $context);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $marginRule;
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][MarginImpact::class]['rule'] = $marginRule;

        $marginRule = $this->denormalizer->denormalize(['impacts' => $marginImpacts], $type, $format, $context);

        return $marginRule;
    }
}
