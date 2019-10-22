<?php

declare(strict_types=1);

namespace Unilend\Identifier\Normalizer;

use ApiPlatform\Core\Identifier\Normalizer\IntegerDenormalizer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class HashDenormalizer implements DenormalizerInterface
{
    public const HASH_REGEX = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    /**
     * @var IntegerDenormalizer
     */
    private $denormalizer;

    /**
     * HashNormalizer constructor.
     *
     * @param IntegerDenormalizer $denormalizer
     */
    public function __construct(IntegerDenormalizer $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed  $data    Data to restore
     * @param string $type    The expected class to instantiate
     * @param string $format  Format the given data was extracted from
     * @param array  $context Options available to the denormalizer
     *
     * @throws ExceptionInterface
     *
     * @return int|string
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return 1 === preg_match(static::HASH_REGEX, $data) ?
            $data : $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     *
     * @param mixed  $data   Data to denormalize from
     * @param string $type   The class to which the data should be denormalized
     * @param string $format The format being deserialized from
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $this->denormalizer->supportsDenormalization($data, $type, $format);
    }
}
