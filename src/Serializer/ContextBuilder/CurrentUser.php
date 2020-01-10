<?php

declare(strict_types=1);

namespace Unilend\Serializer\ContextBuilder;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Clients;
use Unilend\Entity\Companies;

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

        if ($resourceClass && $user instanceof Clients) {
            $reflection  = new ReflectionClass($resourceClass);
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                $parameters = $constructor->getParameters();
                foreach ($parameters as $parameter) {
                    // TODO See if we need a convention about the constructor parameter name (problem with validation)
                    if (($type = $parameter->getType()) && (Clients::class === $type->getName())) {
                        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $user;
                    }

                    if (($type = $parameter->getType()) && (Companies::class === $type->getName()) && ($staff = $user->getStaff())) {
                        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $staff->getCompany();
                    }
                }
            }
        }

        return $context;
    }
}
