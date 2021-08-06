<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\ContextBuilder\Staff;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\StaffStatus;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Syndication\Entity\ProjectParticipationStatus;
use Unilend\Syndication\Entity\ProjectStatus;

class CurrentStaff implements SerializerContextBuilderInterface
{
    private SerializerContextBuilderInterface $decorated;

    private Security $security;

    private UserRepository $userRepository;

    public function __construct(SerializerContextBuilderInterface $decorated, Security $security, UserRepository $userRepository)
    {
        $this->decorated      = $decorated;
        $this->security       = $security;
        $this->userRepository = $userRepository;
    }

    /**
     * Creates a serialization context from a Request.
     *
     * @throws ReflectionException
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context       = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        $user = $this->security->getUser();

        if ($user instanceof UserInterface && false === $user instanceof User) {
            $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user && $user instanceof User) {
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
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectStatus::class]['addedBy']              = $staff;
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][StaffStatus::class]['addedBy']                = $staff;
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationStatus::class]['addedBy'] = $staff;
        }

        return $context;
    }
}
