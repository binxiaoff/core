<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\ContextBuilder;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class CircularReferenceHandler implements SerializerContextBuilderInterface
{
    /** @var SerializerContextBuilderInterface */
    private $decorated;
    /** @var IriConverterInterface */
    private $iriConverter;

    public function __construct(
        SerializerContextBuilderInterface $decorated,
        IriConverterInterface $iriConverter
    ) {
        $this->decorated    = $decorated;
        $this->iriConverter = $iriConverter;
    }

    /**
     * Creates a serialization context from a Request.
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] = function ($object, $format, $context) {
            return $this->iriConverter->getIriFromItem($object);
        };

        return $context;
    }
}
