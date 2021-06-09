<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use Exception;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\BorrowerMember;

class BorrowerMemberNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && BorrowerMember::class === $type;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $archived = $data['archived'] ?? false;
        unset($data['archived']);

        /** @var BorrowerMember $borrowerMember */
        $borrowerMember = $this->denormalizer->denormalize($data, $type, $format, $context);

        if ($archived && $borrowerMember && (false === $borrowerMember->isArchived())) {
            $borrowerMember->archive();
        }

        return $borrowerMember;
    }
}
