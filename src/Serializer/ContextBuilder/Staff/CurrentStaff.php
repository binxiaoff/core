<?php

declare(strict_types=1);

namespace Unilend\Serializer\ContextBuilder\Staff;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\StaffStatus;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectStatus;
use Unilend\Repository\ClientsRepository;

class CurrentStaff implements SerializerContextBuilderInterface
{
    /**  @var SerializerContextBuilderInterface */
    private SerializerContextBuilderInterface $decorated;
    /** @var Security */
    private Security $security;
    /** @var ClientsRepository */
    private ClientsRepository $clientsRepository;

    /**
     * @param SerializerContextBuilderInterface $decorated
     * @param Security                          $security
     * @param ClientsRepository                 $clientsRepository
     */
    public function __construct(SerializerContextBuilderInterface $decorated, Security $security, ClientsRepository $clientsRepository)
    {
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

        if ($user && $user instanceof Clients) {
            $staff = $user->getCurrentStaff();

            if ($resourceClass) {
                $reflection  = new ReflectionClass($resourceClass);
                $constructor = $reflection->getConstructor();
                if ($constructor) {
                    $parameters = $constructor->getParameters();

                    foreach ($parameters as $parameter) {
                        $type = $parameter->getType();
                        if ($type && 'addedBy' === $parameter->getName() && $user->getCurrentStaff() && (Staff::class === $type->getName())) {
                            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter->getName()] = $staff;
                        }
                    }
                }
            }

            // Needed for ProjectStatus because we patch project to change status
            // Put here because there is no need for more advance customisation of their denormalisation
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectStatus::class]['addedBy'] = $staff;
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][StaffStatus::class]['addedBy']   = $staff;
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationStatus::class]['addedBy']   = $staff;
        }

        return $context;
    }
}
