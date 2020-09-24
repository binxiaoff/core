<?php

declare(strict_types=1);

namespace Unilend\Serializer\ContextBuilder;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};
use Unilend\Repository\ClientsRepository;

class CurrentUser implements SerializerContextBuilderInterface
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
     * @return array
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $user = $this->security->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user && $user instanceof Clients) {
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][AcceptationsLegalDocs::class]['acceptedBy'] = $user;
        }

        return $context;
    }
}
