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
use Unilend\Agency\Entity\Term;

class TermNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && $type === Term::class;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $sharing = $data['shared'] ?? false;
        unset($data['shared']);

        /** @var Term $term */
        $term = $this->denormalizer->denormalize($data, $type, $format, $context);

        if ($sharing && $term && (false === $term->isShared())) {
            $term->share();
        }

        return $term;
    }
}
