<?php

declare(strict_types=1);

namespace Unilend\Swagger;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SwaggerDecorator implements NormalizerInterface
{
    private $decorated;

    /**
     * @param NormalizerInterface $decorated
     */
    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param mixed $object
     * @param null  $format
     * @param array $context
     *
     * @throws ExceptionInterface
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        /** @var array $docs */
        $docs = $this->decorated->normalize($object, $format, $context);

        $docs['info']['title']   = 'KLS';
        $docs['info']['version'] = '1.0.0';

        return $docs;
    }

    /**
     * @param mixed $data
     * @param null  $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
