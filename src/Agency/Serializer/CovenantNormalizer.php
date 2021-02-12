<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Exception;
use Symfony\Component\Serializer\Normalizer\{
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait
};
use Unilend\Agency\Entity\Covenant;

class CovenantNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && $type === Covenant::class;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $publication = $data['published'] ?? false;
        unset($data['published']);

        /** @var Covenant $covenant */
        $covenant = $this->denormalizer->denormalize($data, $type, $format, $context);

        if ($publication && $covenant && (false === $covenant->isPublished())) {
            $covenant->publish();
        }

        return $covenant;
    }
}
