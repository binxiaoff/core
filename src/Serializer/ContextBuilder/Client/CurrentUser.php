<?php

declare(strict_types=1);

namespace Unilend\Serializer\ContextBuilder\Client;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Clients;
use Unilend\Entity\Staff;
use Unilend\Repository\ClientsRepository;

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
    /** @var ClientsRepository */
    private $clientsRepository;

    /**
     * @param SerializerContextBuilderInterface $decorated
     * @param Security                          $security
     * @param ClientsRepository                 $clientsRepository
     */
    public function __construct(
        SerializerContextBuilderInterface $decorated,
        Security $security,
        ClientsRepository $clientsRepository
    ) {
        $this->decorated         = $decorated;
        $this->security          = $security;
        $this->clientsRepository = $clientsRepository;
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

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($resourceClass && $user instanceof Clients) {
            $reflection  = new ReflectionClass($resourceClass);
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                $parameters = $constructor->getParameters();

                foreach ($parameters as $parameter) {
                    $type = $parameter->getType();
                    if ($type && 'addedBy' === $parameter->getName()) {
                        if (Clients::class === $type->getName()) {
                            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $user;
                        }

                        if ($user->getCurrentStaff() && (Staff::class === $type->getName())) {
                            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $user->getCurrentStaff();
                        }
                    }
                }
            }
        }

        return $context;
    }
}
