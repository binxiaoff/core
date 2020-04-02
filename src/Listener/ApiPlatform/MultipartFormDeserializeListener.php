<?php

declare(strict_types=1);

namespace Unilend\Listener\ApiPlatform;

use ApiPlatform\Core\EventListener\DeserializeListener as DecoratedListener;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Updates the entity retrieved by the data provider depending on request content-type.
 *
 * If form given (multipart/form-data), data is setted with the request files
 * Otherwise, data is setted with the request body
 */
final class MultipartFormDeserializeListener
{
    /** @var DenormalizerInterface */
    private $denormalizer;
    /** @var SerializerContextBuilderInterface */
    private $serializerContextBuilder;
    /** @var DecoratedListener */
    private $decorated;

    /**
     * @param DenormalizerInterface             $denormalizer
     * @param SerializerContextBuilderInterface $serializerContextBuilder
     * @param DecoratedListener                 $decorated
     */
    public function __construct(DenormalizerInterface $denormalizer, SerializerContextBuilderInterface $serializerContextBuilder, DecoratedListener $decorated)
    {
        $this->denormalizer             = $denormalizer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->decorated                = $decorated;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws ExceptionInterface
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (
            $request->isMethodSafe()
            || $request->isMethod(Request::METHOD_DELETE)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
        ) {
            return;
        }

        'form' === $request->getContentType() ? $this->denormalizeFormRequest($request, $attributes) : $this->decorated->onKernelRequest($event);
    }

    /**
     * @param Request $request
     * @param array   $attributes
     *
     * @throws ExceptionInterface
     */
    private function denormalizeFormRequest(Request $request, array $attributes): void
    {
        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);

        if (null !== $objectToPopulate = $request->attributes->get('data')) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $objectToPopulate;
        }

        $data              = [];
        $requestParameters = array_merge($request->files->all(), $request->request->all());

        foreach ($requestParameters as $requestParameterKey => $requestParameterValue) {
            $data[$requestParameterKey] = $requestParameterValue;
        }

        $request->attributes->set(
            'data',
            $this->denormalizer->denormalize(
                $data,
                $attributes['resource_class'],
                null,
                $context
            )
        );
    }
}
