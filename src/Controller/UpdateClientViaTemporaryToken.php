<?php

declare(strict_types=1);

namespace Unilend\Controller;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Unilend\Entity\{Clients, TemporaryToken};
use Unilend\Repository\ClientsRepository;

class UpdateClientViaTemporaryToken extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var ClientsRepository */
    private $clientRepository;
    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param SerializerInterface $serializer
     * @param ClientsRepository   $clientRepository
     * @param ValidatorInterface  $validator
     */
    public function __construct(SerializerInterface $serializer, ClientsRepository $clientRepository, ValidatorInterface $validator)
    {
        $this->serializer       = $serializer;
        $this->clientRepository = $clientRepository;
        $this->validator        = $validator;
    }

    /**
     * @param TemporaryToken $data
     * @param Request        $request
     *
     * @throws Exception
     *
     * @return Clients
     */
    public function __invoke(TemporaryToken $data, Request $request): Clients
    {
        if (false === $data->isValid()) {
            throw new AccessDeniedHttpException('Temporary token expired');
        }

        $client = $data->getClient();
        $this->serializer->deserialize($request->getContent(), Clients::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $client]);
        $violations = $this->validator->validate($client);

        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }

        $this->clientRepository->save($client);

        return $client;
    }
}
