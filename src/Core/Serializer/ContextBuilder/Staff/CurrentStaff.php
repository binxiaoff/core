<?php

declare(strict_types=1);

namespace KLS\Core\Serializer\ContextBuilder\Staff;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\StaffStatus;
use KLS\Core\Entity\User;
use KLS\Core\Repository\UserRepository;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class CurrentStaff implements SerializerContextBuilderInterface
{
    private SerializerContextBuilderInterface $decorated;
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(
        SerializerContextBuilderInterface $decorated,
        Security $security,
        UserRepository $userRepository
    ) {
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

        if ($user instanceof User) {
            $staff   = $user->getCurrentStaff();
            $company = $staff ? $staff->getCompany() : null;

            if ($resourceClass) {
                $reflection  = new ReflectionClass($resourceClass);
                $constructor = $reflection->getConstructor();
                if ($constructor) {
                    $parameters = $constructor->getParameters();

                    foreach ($parameters as $parameter) {
                        $type = $parameter->getType();
                        if (!$type) {
                            continue;
                        }
                        if ('addedBy' === $parameter->getName()) {
                            if ($staff && (Staff::class === $type->getName())) {
                                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter
                                    ->getName()] = $staff;
                            }
                            if (User::class === $type->getName()) {
                                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter
                                    ->getName()] = $user;
                            }
                        }
                        if (
                            $company
                            && 'addedByCompany' === $parameter->getName()
                            && (Company::class === $type->getName())
                        ) {
                            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$resourceClass][$parameter
                                ->getName()] = $company;
                        }
                    }
                }
            }

            // Put here because there is no need for more advance customisation of their denormalisation
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][StaffStatus::class]['addedBy'] = $staff;
        }

        return $context;
    }
}
