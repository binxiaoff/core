<?php

declare(strict_types=1);

namespace KLS\Core\Serializer\ContextBuilder;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use KLS\Core\Entity\AcceptationsLegalDocs;
use KLS\Core\Entity\User;
use KLS\Core\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class CurrentUser implements SerializerContextBuilderInterface
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
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $user = $this->security->getUser();

        if ($user instanceof UserInterface && false === $user instanceof User) {
            $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user && $user instanceof User) {
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][AcceptationsLegalDocs::class]['acceptedBy'] = $user;
        }

        return $context;
    }
}
