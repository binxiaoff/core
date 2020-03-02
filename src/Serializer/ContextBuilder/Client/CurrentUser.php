<?php

declare(strict_types=1);

namespace Unilend\Serializer\ContextBuilder\Client;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Clients;
use Unilend\Entity\Staff;

class CurrentUser implements SerializerContextBuilderInterface
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

        $user = $this->security->getUser();

        if ($resourceClass) {
            $reflection  = new ReflectionClass($resourceClass);
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                $parameters = $constructor->getParameters();

                foreach ($parameters as $parameter) {
                    if ($user instanceof Clients && ($type = $parameter->getType()) && (Clients::class === $type->getName()) && 'addedBy' === $parameter->getName()) {
                        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $user;
                    }

                    if ($user instanceof Staff && ($type = $parameter->getType()) && (Staff::class === $type->getName()) && 'addedBy' === $parameter->getName()) {
                        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $user;
                    }
                }
            }
        }

        return $context;
    }
}
