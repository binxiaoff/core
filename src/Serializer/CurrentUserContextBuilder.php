<?php

declare(strict_types=1);

namespace Unilend\Serializer;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Clients;

class CurrentUserContextBuilder implements SerializerContextBuilderInterface
{
    /**
     * @var SerializerContextBuilderInterface
     */
    private $decorated;

    /**
     * @var Security
     */
    private $security;

    /**
     * CurrentUserContextBuilder constructor.
     *
     * @param SerializerContextBuilderInterface $decorated
     * @param Security                          $security
     */
    public function __construct(SerializerContextBuilderInterface $decorated, Security $security)
    {
        $this->decorated = $decorated;
        $this->security  = $security;
    }

    /**
     * Creates a serialization context from a Request.
     *
     * @param Request    $request
     * @param bool       $normalization
     * @param array|null $extractedAttributes
     *
     * @throws ReflectionException
     *
     * @return array
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context       = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        if ($resourceClass) {
            $user = $this->security->getUser();

            $reflection = new ReflectionClass($resourceClass);
            $parameters = $reflection->getConstructor()->getParameters();

            foreach ($parameters as $parameter) {
                if (($type = $parameter->getType()) && (Clients::class === $type->getName())) {
                    $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $user;
                }
            }
        }

        return $context;
    }
}
