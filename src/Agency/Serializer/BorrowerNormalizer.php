<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Unilend\Agency\Entity\Borrower;
use Unilend\Agency\Entity\BorrowerMember;

class BorrowerNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return Borrower::class === $type && false === isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $signatory = $data['signatory'] ?? null;
        unset($data['signatory']);
        $referent = $data['referent'] ?? null;
        unset($data['referent']);

        $borrower = $this->denormalizer->denormalize($data, $type, $format, $context);

        $context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $borrower;
        $context[AbstractObjectNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][BorrowerMember::class]['borrower'] = $borrower;

        $borrower = $this->denormalizer->denormalize(['signatory' => $signatory, 'referent' => $referent], $type, $format, $context);

        return $borrower;
    }
}
