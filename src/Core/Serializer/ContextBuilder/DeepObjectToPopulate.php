<?php

declare(strict_types=1);

namespace KLS\Core\Serializer\ContextBuilder;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Temporary measure against possible BC Break in 2.6.*.
 */
class DeepObjectToPopulate implements SerializerContextBuilderInterface
{
    private SerializerContextBuilderInterface $decorated;

    public function __construct(SerializerContextBuilderInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * Creates a serialization context from a Request.
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] = false;
        $context['deep_object_to_populate']                         = false;

        return $context;
    }
}
